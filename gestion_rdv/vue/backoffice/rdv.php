<?php
require_once __DIR__ . '/../../../inc_session.php';

checkSession([2,3,4]);

$user = getCurrentUser();

$nom = $user['Nom'] ?? $user['nom'] ?? '';
$prenom = $user['Prenom'] ?? $user['prenom'] ?? $user['username'] ?? 'Utilisateur';
$role = (int)($user['id_role'] ?? 0);

$initial = strtoupper(substr($prenom, 0, 1));

$roleLabel = match ($role) {
    2 => 'Admin',
    3 => 'Médecin',
    4 => 'Super Admin',
    default => 'Utilisateur'
};
?>
<?php
require_once dirname(__DIR__, 3) . '/config.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$db     = config::getConnexion();

function table_columns(PDO $db, string $table): array {
    $stmt = $db->prepare("SHOW COLUMNS FROM `$table`");
    $stmt->execute();
    return array_map(fn($r) => $r['Field'], $stmt->fetchAll(PDO::FETCH_ASSOC));
}
function has_col(array $cols, string $name): bool { return in_array($name, $cols, true); }
function creneau_date_col(PDO $db): string {
    $cols = table_columns($db, 'creneau');
    if (has_col($cols, 'date_creneau')) return 'date_creneau';
    if (has_col($cols, 'date'))         return 'date';
    return 'date_creneau';
}
function json_ok(array $extra = [])  { echo json_encode(['success' => true]  + $extra); exit; }
function json_fail(string $message)  { echo json_encode(['success' => false, 'message' => $message]); exit; }

try {
    $dateCol = creneau_date_col($db);

    /* ── Liste des patients (id_role = 1) ─────────────────── */
    if ($action === 'patients') {
        $sql = "SELECT id_user,
                       TRIM(CONCAT(COALESCE(Prenom,''), ' ', COALESCE(Nom,''))) AS nom_complet
                FROM user
                WHERE id_role = 1
                ORDER BY Prenom, Nom, id_user";
        $q = $db->prepare($sql);
        $q->execute();
        json_ok(['data' => $q->fetchAll(PDO::FETCH_ASSOC)]);
    }

    /* ── Chargement de tous les RDV ───────────────────────── */
    if ($action === 'charger') {
        $sql = "SELECT rv.id_rdv,
                       rv.date_demande,
                       COALESCE(rv.date_rdv, c.`$dateCol`) AS date_rdv,
                       rv.statut,
                       rv.type_consultation,
                       rv.id_patient,
                       rv.id_creneau,
                       rv.id_medecin,
                       TRIM(CONCAT(COALESCE(m.Prenom,''), ' ', COALESCE(m.Nom,''))) AS nom_medecin,
                       TRIM(CONCAT(COALESCE(p.Prenom,''), ' ', COALESCE(p.Nom,''))) AS nom_patient
                FROM rendez_vous rv
                LEFT JOIN creneau c ON c.id_creneau = rv.id_creneau
                LEFT JOIN user m    ON m.id_user    = rv.id_medecin
                LEFT JOIN user p    ON p.id_user    = rv.id_patient
                ORDER BY rv.date_demande DESC, rv.id_rdv DESC";
        $q = $db->prepare($sql);
        $q->execute();
        json_ok(['data' => $q->fetchAll(PDO::FETCH_ASSOC)]);
    }

    /* ── Ajout d'un RDV ───────────────────────────────────── */
    if ($action === 'ajouter') {
        $patient = trim($_POST['patient'] ?? '');
        $date    = trim($_POST['date_demande'] ?? '');
        $type    = trim($_POST['type']    ?? '');
        $medecin = trim($_POST['medecin'] ?? '');
        $creneau = trim($_POST['creneau'] ?? '');

        if ($patient === '' || $date === '' || $type === '' || $medecin === '' || $creneau === '')
            json_fail('champs_manquants');
        if (!ctype_digit($patient)) json_fail('patient_invalide');
        if (!ctype_digit($medecin)) json_fail('medecin_invalide');
        if (!ctype_digit($creneau)) json_fail('creneau_invalide');

        $patient = (int)$patient;
        $medecin = (int)$medecin;
        $creneau = (int)$creneau;

        /* Vérification patient (id_role = 1) */
        $q = $db->prepare("SELECT id_user FROM user WHERE id_user = :id AND id_role = 1");
        $q->execute([':id' => $patient]);
        if (!$q->fetch()) json_fail('patient_introuvable');

        /* Vérification médecin (id_role = 3) */
        $q = $db->prepare("SELECT id_user FROM user WHERE id_user = :id AND id_role = 3");
        $q->execute([':id' => $medecin]);
        if (!$q->fetch()) json_fail('medecin_introuvable');

        /* Vérification créneau */
        $q = $db->prepare("SELECT statut, id_medecin, `$dateCol` AS date_creneau
                           FROM creneau WHERE id_creneau = :creneau");
        $q->execute([':creneau' => $creneau]);
        $row = $q->fetch(PDO::FETCH_ASSOC);
        if (!$row) json_fail('creneau_introuvable');
        if (strtolower((string)$row['statut']) === 'reserve') json_fail('creneau_pris');
        if ((int)$row['id_medecin'] !== $medecin) json_fail('creneau_medecin_invalide');

        /* Génération d'un id_rdv entier unique */
        do {
            $idRdv = random_int(1, 99999999);
            $check = $db->prepare("SELECT id_rdv FROM rendez_vous WHERE id_rdv = :id");
            $check->execute([':id' => $idRdv]);
        } while ($check->fetch());

        $db->beginTransaction();
        $q = $db->prepare("INSERT INTO rendez_vous
                               (id_rdv, date_demande, date_rdv, statut, type_consultation,
                                id_patient, id_creneau, id_medecin)
                           VALUES
                               (:id, :date, :date_rdv, 'en_attente', :type, :patient, :creneau, :medecin)");
        $q->execute([
            ':id'       => $idRdv,
            ':date'     => $date,
            ':date_rdv' => $row['date_creneau'],
            ':type'     => $type,
            ':patient'  => $patient,
            ':creneau'  => $creneau,
            ':medecin'  => $medecin,
        ]);
        $q = $db->prepare("UPDATE creneau SET statut = 'reserve' WHERE id_creneau = :creneau");
        $q->execute([':creneau' => $creneau]);
        $db->commit();
        json_ok(['message' => 'RDV ajouté', 'id' => $idRdv]);
    }

    /* ── Modification d'un RDV ────────────────────────────── */
    if ($action === 'modifier') {
        $id      = trim($_POST['id']      ?? '');
        $date    = trim($_POST['date']    ?? '');
        $dateRdv = trim($_POST['date_rdv'] ?? '');
        $type    = trim($_POST['type']    ?? '');
        $statut  = strtolower(trim($_POST['statut'] ?? ''));
        $map     = ['confirmé'=>'confirme','refusé'=>'refuse','annulé'=>'annule'];
        $statut  = $map[$statut] ?? $statut;
        $statutsAutorises = ['confirme','en_attente','refuse','annule','a_confirmer','a_reporter'];

        if ($id === '' || $date === '' || $dateRdv === '' || $type === '' || $statut === '')
            json_fail('champs_manquants');
        if (!ctype_digit($id))                          json_fail('id_invalide');
        if (!in_array($statut, $statutsAutorises, true)) json_fail('statut_invalide');

        $id = (int)$id;
        $db->beginTransaction();

        $q = $db->prepare("SELECT id_creneau FROM rendez_vous WHERE id_rdv = :id");
        $q->execute([':id' => $id]);
        $row = $q->fetch(PDO::FETCH_ASSOC);
        if (!$row) { $db->rollBack(); json_fail('rdv_introuvable'); }

        $q = $db->prepare("UPDATE rendez_vous
                           SET date_demande = :date, date_rdv = :date_rdv,
                               type_consultation = :type, statut = :statut
                           WHERE id_rdv = :id");
        $q->execute([':date' => $date, ':date_rdv' => $dateRdv, ':type' => $type, ':statut' => $statut, ':id' => $id]);

        if (!empty($row['id_creneau'])) {
            $newStatut = in_array($statut, ['refuse','annule','a_reporter'], true) ? 'disponible' : 'reserve';
            $q = $db->prepare("UPDATE creneau SET statut = :statut WHERE id_creneau = :id");
            $q->execute([':statut' => $newStatut, ':id' => (int)$row['id_creneau']]);
        }
        $db->commit();
        json_ok(['message' => 'RDV modifié']);
    }

    /* ── Changement de statut rapide ──────────────────────── */
    if ($action === 'changer_statut') {
        $id     = trim($_POST['id']     ?? '');
        $statut = strtolower(trim($_POST['statut'] ?? ''));
        $map    = ['confirmé'=>'confirme','refusé'=>'refuse','annulé'=>'annule'];
        $statut = $map[$statut] ?? $statut;
        $autorisés = ['confirme','en_attente','refuse','annule','a_confirmer','a_reporter'];

        if ($id === '' || !ctype_digit($id) || !in_array($statut, $autorisés, true))
            json_fail('parametres_invalides');
        $id = (int)$id;

        $db->beginTransaction();
        $q = $db->prepare("SELECT id_creneau FROM rendez_vous WHERE id_rdv = :id");
        $q->execute([':id' => $id]);
        $row = $q->fetch(PDO::FETCH_ASSOC);
        if (!$row) { $db->rollBack(); json_fail('rdv_introuvable'); }

        $q = $db->prepare("UPDATE rendez_vous SET statut = :statut WHERE id_rdv = :id");
        $q->execute([':statut' => $statut, ':id' => $id]);

        if (!empty($row['id_creneau'])) {
            $newStatut = in_array($statut, ['refuse','annule','a_reporter'], true) ? 'disponible' : 'reserve';
            $q = $db->prepare("UPDATE creneau SET statut = :statut WHERE id_creneau = :id");
            $q->execute([':statut' => $newStatut, ':id' => (int)$row['id_creneau']]);
        }
        $db->commit();
        json_ok(['message' => 'Statut RDV mis à jour']);
    }

    /* ── Suppression d'un RDV ─────────────────────────────── */
    if ($action === 'supprimer') {
        $id = trim($_POST['id'] ?? '');
        if ($id === '' || !ctype_digit($id)) json_fail('id_manquant');
        $id = (int)$id;

        $db->beginTransaction();
        $q = $db->prepare("SELECT id_creneau FROM rendez_vous WHERE id_rdv = :id");
        $q->execute([':id' => $id]);
        $row = $q->fetch(PDO::FETCH_ASSOC);
        if (!$row) { $db->rollBack(); json_fail('rdv_introuvable'); }

        $q = $db->prepare("DELETE FROM rendez_vous WHERE id_rdv = :id");
        $q->execute([':id' => $id]);

        if (!empty($row['id_creneau'])) {
            $q = $db->prepare("UPDATE creneau SET statut = 'disponible' WHERE id_creneau = :id");
            $q->execute([':id' => (int)$row['id_creneau']]);
        }
        $db->commit();
        json_ok(['message' => 'RDV supprimé']);
    }

    json_fail('action_inconnue');

} catch (Exception $e) {
    if ($db instanceof PDO && $db->inTransaction()) $db->rollBack();
    json_fail($e->getMessage());
}
?>