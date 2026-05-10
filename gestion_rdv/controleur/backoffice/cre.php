<?php
require_once dirname(__DIR__, 3) . '/config.php';
class CreneauC
{
    /* ── AJOUT ─────────────────────────────────────────────── */
    function addCreneau($creneau)
    {
        $sql = "INSERT INTO creneau
                    (id_creneau, date_creneau, heure_debut, heure_fin, statut, id_medecin)
                VALUES
                    (:id, :dateCreneau, :heureDebut, :heureFin, :statut, :idMedecin)";
        $db = config::getConnexion();
        try {
            $query = $db->prepare($sql);
            $query->execute([
                'id'          => $creneau->getIdCreneau(),
                'dateCreneau' => $creneau->getDateCreneau(),
                'heureDebut'  => $creneau->getHeureDebut(),
                'heureFin'    => $creneau->getHeureFin(),
                'statut'      => $creneau->getStatut(),
                'idMedecin'   => $creneau->getIdMedecin(),
            ]);
        } catch (Exception $e) {
            echo 'Erreur : ' . $e->getMessage();
        }
    }

    /* ── MODIFICATION ───────────────────────────────────────── */
    function updateCreneau($creneau, $id)
    {
        try {
            $db    = config::getConnexion();
            $query = $db->prepare(
                'UPDATE creneau SET
                    date_creneau = :dateCreneau,
                    heure_debut  = :heureDebut,
                    heure_fin    = :heureFin,
                    statut       = :statut,
                    id_medecin   = :idMedecin
                WHERE id_creneau = :id'
            );
            $query->execute([
                'id'          => (int)$id,
                'dateCreneau' => $creneau->getDateCreneau(),
                'heureDebut'  => $creneau->getHeureDebut(),
                'heureFin'    => $creneau->getHeureFin(),
                'statut'      => $creneau->getStatut(),
                'idMedecin'   => $creneau->getIdMedecin(),
            ]);
        } catch (PDOException $e) {
            echo 'Erreur : ' . $e->getMessage();
        }
    }

    /* ── SUPPRESSION ────────────────────────────────────────── */
    function deleteCreneau($ide)
    {
        $sql = "DELETE FROM creneau WHERE id_creneau = :id";
        $db  = config::getConnexion();
        $req = $db->prepare($sql);
        $req->bindValue(':id', (int)$ide, PDO::PARAM_INT);
        try {
            $req->execute();
        } catch (Exception $e) {
            die('Erreur : ' . $e->getMessage());
        }
    }

    /* ── LISTE COMPLÈTE ─────────────────────────────────────── */
    function getAllCreneau()
    {
        $sql = "SELECT * FROM creneau";
        $db  = config::getConnexion();
        try {
            $query = $db->prepare($sql);
            $query->execute();
            return $query->fetchAll();
        } catch (Exception $e) {
            echo 'Erreur : ' . $e->getMessage();
        }
    }
}

/* ═══════════════════════════════════════════════════════════
   API JSON — actions appelées par le JavaScript backoffice
   ═══════════════════════════════════════════════════════════ */
if (isset($_GET['action'])) {
    header('Content-Type: application/json; charset=utf-8');

    $db = config::getConnexion();

    /* Détection automatique du nom de la colonne date */
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
    function json_ok(array $extra = [])      { echo json_encode(['success' => true]  + $extra); exit; }
    function json_fail(string $message)      { echo json_encode(['success' => false, 'message' => $message]); exit; }

    try {
        $dateCol = creneau_date_col($db);
        $action  = $_GET['action'] ?? '';

        /* ── Liste des médecins (id_role = 3) ───────────────── */
        if ($action === 'medecins') {
            $sql = "SELECT id_user,
                           TRIM(CONCAT(COALESCE(Prenom,''), ' ', COALESCE(Nom,''))) AS nom_complet
                    FROM user
                    WHERE id_role = 3
                      AND LOWER(Statut_Cmpt) = 'actif'
                    ORDER BY Prenom, Nom";
            $q = $db->prepare($sql);
            $q->execute();
            json_ok(['data' => $q->fetchAll(PDO::FETCH_ASSOC)]);
        }

        /* ── Liste des patients (id_role = 1) ───────────────── */
        if ($action === 'patients') {
            $sql = "SELECT id_user,
                           TRIM(CONCAT(COALESCE(Prenom,''), ' ', COALESCE(Nom,''))) AS nom_complet
                    FROM user
                    WHERE id_role = 1
                    ORDER BY Prenom, Nom";
            $q = $db->prepare($sql);
            $q->execute();
            json_ok(['data' => $q->fetchAll(PDO::FETCH_ASSOC)]);
        }

        /* ── Chargement des créneaux (tous ou par médecin) ──── */
        if ($action === 'charger' || $action === 'charger_medecin') {
            $where  = '';
            $params = [];
            if ($action === 'charger_medecin') {
                $medecin = trim($_GET['medecin'] ?? '');
                if ($medecin === '' || !ctype_digit($medecin)) json_fail('medecin_manquant');
                $where            = 'WHERE c.id_medecin = :medecin';
                $params[':medecin'] = (int)$medecin;
            }
            $sql = "SELECT c.id_creneau,
                           c.`$dateCol` AS date,
                           c.heure_debut, c.heure_fin, c.statut, c.id_medecin,
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

        /* ── Ajout d'un créneau ─────────────────────────────── */
        if ($action === 'ajouter') {
            $date    = trim($_POST['date']    ?? '');
            $debut   = trim($_POST['debut']   ?? '');
            $fin     = trim($_POST['fin']     ?? '');
            $medecin = trim($_POST['medecin'] ?? '');

            if ($date === '' || $debut === '' || $fin === '' || $medecin === '') json_fail('champs_manquants');
            if (!ctype_digit($medecin)) json_fail('medecin_invalide');
            if ($date < date('Y-m-d'))  json_fail('date_invalide');
            if ($debut >= $fin)         json_fail('heure_invalide');

            $medecin = (int)$medecin;

            $q = $db->prepare("SELECT id_user FROM user WHERE id_user = :id AND id_role = 3");
            $q->execute([':id' => $medecin]);
            if (!$q->fetch()) json_fail('medecin_introuvable');

            $q = $db->prepare("SELECT id_creneau FROM creneau
                               WHERE id_medecin = :medecin AND `$dateCol` = :date
                                 AND :debut < heure_fin AND :fin > heure_debut");
            $q->execute([':medecin' => $medecin, ':date' => $date, ':debut' => $debut, ':fin' => $fin]);
            if ($q->fetch()) json_fail('creneau_existe');

            /* Génération d'un id_creneau entier unique */
            do {
                $id    = random_int(1, 99999999);
                $check = $db->prepare("SELECT id_creneau FROM creneau WHERE id_creneau = :id");
                $check->execute([':id' => $id]);
            } while ($check->fetch());

            $q = $db->prepare("INSERT INTO creneau (id_creneau, `$dateCol`, heure_debut, heure_fin, statut, id_medecin)
                               VALUES (:id, :date, :debut, :fin, 'disponible', :medecin)");
            $q->execute([':id' => $id, ':date' => $date, ':debut' => $debut, ':fin' => $fin, ':medecin' => $medecin]);
            json_ok(['message' => 'Créneau ajouté', 'id' => $id]);
        }

        /* ── Modification d'un créneau ──────────────────────── */
        if ($action === 'modifier') {
            $id    = trim($_POST['id']    ?? '');
            $date  = trim($_POST['date']  ?? '');
            $debut = trim($_POST['debut'] ?? '');
            $fin   = trim($_POST['fin']   ?? '');
            if ($id === '' || $date === '' || $debut === '' || $fin === '') json_fail('champs_manquants');
            if (!ctype_digit($id)) json_fail('id_invalide');
            if ($date < date('Y-m-d')) json_fail('date_invalide');
            if ($debut >= $fin)        json_fail('heure_invalide');

            $q = $db->prepare("UPDATE creneau
                               SET `$dateCol` = :date, heure_debut = :debut, heure_fin = :fin
                               WHERE id_creneau = :id");
            $q->execute([':date' => $date, ':debut' => $debut, ':fin' => $fin, ':id' => (int)$id]);
            json_ok(['message' => 'Créneau modifié']);
        }

        /* ── Suppression d'un créneau ───────────────────────── */
        if ($action === 'supprimer') {
            $id = trim($_POST['id'] ?? '');
            if ($id === '' || !ctype_digit($id)) json_fail('id_manquant');
            $id = (int)$id;

            $q = $db->prepare("SELECT statut FROM creneau WHERE id_creneau = :id");
            $q->execute([':id' => $id]);
            $row = $q->fetch(PDO::FETCH_ASSOC);
            if (!$row) json_fail('creneau_introuvable');
            if (strtolower((string)$row['statut']) === 'reserve') json_fail('creneau_reserve');

            $q = $db->prepare("DELETE FROM creneau WHERE id_creneau = :id");
            $q->execute([':id' => $id]);
            json_ok(['message' => 'Créneau supprimé']);
        }

        json_fail('action_inconnue');

    } catch (Exception $e) {
        json_fail($e->getMessage());
    }
}
?>