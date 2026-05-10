<?php
require_once dirname(__DIR__, 3) . '/config.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$db = config::getConnexion();

function table_columns(PDO $db, string $table): array {
    $stmt = $db->prepare("SHOW COLUMNS FROM `$table`");
    $stmt->execute();
    return array_map(fn($r) => $r['Field'], $stmt->fetchAll(PDO::FETCH_ASSOC));
}
function creneau_date_col(PDO $db): string {
    $cols = table_columns($db, 'creneau');
    if (in_array('date_creneau', $cols, true)) return 'date_creneau';
    if (in_array('date', $cols, true)) return 'date';
    return 'date_creneau';
}
function json_ok(array $extra = []) { echo json_encode(['success' => true] + $extra); exit; }
function json_fail(string $message) { echo json_encode(['success' => false, 'message' => $message]); exit; }

try {
    $dateCol = creneau_date_col($db);

    if ($action === 'medecins') {
        $sql = "SELECT id_user, TRIM(CONCAT(COALESCE(Prenom,''), ' ', COALESCE(Nom,''))) AS nom_complet
                FROM user
                WHERE id_role IN ('3', 3, 'ROLE-MED', 'ROLE_MED', 'MEDECIN', 'medecin')
                   OR id_user LIKE 'MED-%'
                ORDER BY Prenom, Nom, id_user";
        $q = $db->prepare($sql);
        $q->execute();
        json_ok(['data' => $q->fetchAll(PDO::FETCH_ASSOC)]);
    }

    if ($action === 'patients') {
        $sql = "SELECT id_user, TRIM(CONCAT(COALESCE(Prenom,''), ' ', COALESCE(Nom,''))) AS nom_complet
                FROM user
                WHERE id_role IN ('1', 1, 'ROLE-PAT', 'ROLE_PAT', 'PATIENT', 'patient')
                   OR id_user LIKE 'P-%'
                   OR id_user LIKE 'PAT-%'
                ORDER BY Prenom, Nom, id_user";
        $q = $db->prepare($sql);
        $q->execute();
        json_ok(['data' => $q->fetchAll(PDO::FETCH_ASSOC)]);
    }

    if ($action === 'charger' || $action === 'charger_medecin') {
        $where = '';
        $params = [];
        if ($action === 'charger_medecin') {
            $medecin = trim($_GET['medecin'] ?? '');
            if ($medecin === '') json_fail('medecin_manquant');
            $where = 'WHERE c.id_medecin = :medecin';
            $params[':medecin'] = $medecin;
        }
        $sql = "SELECT c.id_creneau, c.`$dateCol` AS date, c.heure_debut, c.heure_fin,
                       c.statut, c.id_medecin,
                       TRIM(CONCAT(COALESCE(u.Prenom,''), ' ', COALESCE(u.Nom,''))) AS nom_medecin
                FROM creneau c
                LEFT JOIN user u ON u.id_user = c.id_medecin
                $where
                ORDER BY c.`$dateCol` ASC, c.heure_debut ASC";
        $q = $db->prepare($sql);
        $q->execute($params);
        $data = $q->fetchAll(PDO::FETCH_ASSOC);
        json_ok(['data' => $data, 'creneaux' => $data]);
    }

    if ($action === 'ajouter') {
        $date = trim($_POST['date'] ?? '');
        $debut = trim($_POST['debut'] ?? '');
        $fin = trim($_POST['fin'] ?? '');
        $medecin = trim($_POST['medecin'] ?? '');
        if ($date === '' || $debut === '' || $fin === '' || $medecin === '') json_fail('champs_manquants');
        if ($date < date('Y-m-d')) json_fail('date_invalide');
        if ($debut >= $fin) json_fail('heure_invalide');

        $q = $db->prepare("SELECT id_user FROM user WHERE id_user = :id");
        $q->execute([':id'=>$medecin]);
        if (!$q->fetch(PDO::FETCH_ASSOC)) json_fail('medecin_introuvable');

        $q = $db->prepare("SELECT id_creneau FROM creneau
                           WHERE id_medecin = :medecin AND `$dateCol` = :date
                             AND :debut < heure_fin AND :fin > heure_debut");
        $q->execute([':medecin'=>$medecin, ':date'=>$date, ':debut'=>$debut, ':fin'=>$fin]);
        if ($q->fetch(PDO::FETCH_ASSOC)) json_fail('creneau_existe');

        do {
            $id = 'CR-' . str_pad((string)random_int(1,999), 3, '0', STR_PAD_LEFT);
            $check = $db->prepare("SELECT id_creneau FROM creneau WHERE id_creneau = :id");
            $check->execute([':id'=>$id]);
        } while ($check->fetch(PDO::FETCH_ASSOC));

        $q = $db->prepare("INSERT INTO creneau (id_creneau, `$dateCol`, heure_debut, heure_fin, statut, id_medecin)
                           VALUES (:id, :date, :debut, :fin, 'disponible', :medecin)");
        $q->execute([':id'=>$id, ':date'=>$date, ':debut'=>$debut, ':fin'=>$fin, ':medecin'=>$medecin]);
        json_ok(['message'=>'Créneau ajouté', 'id'=>$id]);
    }

    if ($action === 'modifier') {
        $id = trim($_POST['id'] ?? '');
        $date = trim($_POST['date'] ?? '');
        $debut = trim($_POST['debut'] ?? '');
        $fin = trim($_POST['fin'] ?? '');
        if ($id === '' || $date === '' || $debut === '' || $fin === '') json_fail('champs_manquants');
        if ($date < date('Y-m-d')) json_fail('date_invalide');
        if ($debut >= $fin) json_fail('heure_invalide');
        $q = $db->prepare("UPDATE creneau SET `$dateCol` = :date, heure_debut = :debut, heure_fin = :fin WHERE id_creneau = :id");
        $q->execute([':date'=>$date, ':debut'=>$debut, ':fin'=>$fin, ':id'=>$id]);
        json_ok(['message'=>'Créneau modifié']);
    }

    if ($action === 'supprimer') {
        $id = trim($_POST['id'] ?? '');
        if ($id === '') json_fail('id_manquant');
        $q = $db->prepare("SELECT statut FROM creneau WHERE id_creneau = :id");
        $q->execute([':id'=>$id]);
        $row = $q->fetch(PDO::FETCH_ASSOC);
        if (!$row) json_fail('creneau_introuvable');
        if (strtolower((string)$row['statut']) === 'reserve') json_fail('creneau_reserve');
        $q = $db->prepare("DELETE FROM creneau WHERE id_creneau = :id");
        $q->execute([':id'=>$id]);
        json_ok(['message'=>'Créneau supprimé']);
    }

    json_fail('action_inconnue');
} catch (Exception $e) {
    json_fail($e->getMessage());
}
