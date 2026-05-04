<?php
require '../../config.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$db     = config::getConnexion();

/* ─────────────────────────────────────────────────────────
   ACTION: charger
   List all appointments for a patient
   ───────────────────────────────────────────────────────── */
if ($action === 'charger') {
    $id_patient = trim($_GET['patient'] ?? '');

    if ($id_patient === '') {
        echo json_encode(["success" => false, "message" => "ID patient manquant"]);
        exit;
    }

    try {
        $sql = "SELECT rv.id_rdv,
                       rv.date_demande,
                       COALESCE(rv.date_rdv, c.date_creneau) AS date_rdv,
                       c.heure_debut,
                       c.heure_fin,
                       rv.statut,
                       rv.type_consultation,
                       rv.id_creneau,
                       rv.id_medecin,
                       CONCAT(COALESCE(u.Prenom,''), ' ', COALESCE(u.Nom,'')) AS nom_medecin
                FROM rendez_vous rv
                LEFT JOIN creneau c ON c.id_creneau = rv.id_creneau
                LEFT JOIN user u    ON u.id_user    = rv.id_medecin
                WHERE rv.id_patient = :patient
                ORDER BY rv.date_demande DESC, rv.id_rdv DESC";

        $query = $db->prepare($sql);
        $query->execute([':patient' => $id_patient]);

        echo json_encode([
            "success" => true,
            "data"    => $query->fetchAll(PDO::FETCH_ASSOC)
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => $e->getMessage()
        ]);
    }

/* ─────────────────────────────────────────────────────────
   ACTION: medecins
   ───────────────────────────────────────────────────────── */
} elseif ($action === 'medecins') {
    try {
        $sql = "SELECT id_user,
                       CONCAT(COALESCE(Prenom,''), ' ', COALESCE(Nom,'')) AS nom_complet,
                       Service
                FROM user
                WHERE id_role = 'ROLE-MED'
                ORDER BY Prenom, Nom";

        $query = $db->prepare($sql);
        $query->execute();

        echo json_encode([
            "success" => true,
            "data"    => $query->fetchAll(PDO::FETCH_ASSOC)
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => $e->getMessage()
        ]);
    }

/* ─────────────────────────────────────────────────────────
   ACTION: creneaux_dispo
   ───────────────────────────────────────────────────────── */
} elseif ($action === 'creneaux_dispo') {
    $id_medecin = trim($_GET['medecin'] ?? '');

    if ($id_medecin === '') {
        echo json_encode(["success" => false, "message" => "ID médecin manquant"]);
        exit;
    }

    try {
        $sql = "SELECT id_creneau,
                       date_creneau,
                       heure_debut,
                       heure_fin,
                       statut
                FROM creneau
                WHERE id_medecin = :medecin
                  AND statut = 'disponible'
                  AND date_creneau >= CURDATE()
                ORDER BY date_creneau ASC, heure_debut ASC";

        $query = $db->prepare($sql);
        $query->execute([':medecin' => $id_medecin]);

        echo json_encode([
            "success"  => true,
            "creneaux" => $query->fetchAll(PDO::FETCH_ASSOC)
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => $e->getMessage()
        ]);
    }

/* ─────────────────────────────────────────────────────────
   ACTION: ajouter
   Book an appointment
   ───────────────────────────────────────────────────────── */
} elseif ($action === 'ajouter') {
    $id_patient = trim($_POST['patient'] ?? '');
    $date       = trim($_POST['date_demande'] ?? date('Y-m-d'));
    $type       = trim($_POST['type'] ?? '');
    $id_medecin = trim($_POST['medecin'] ?? '');
    $id_creneau = trim($_POST['creneau'] ?? '');

    if ($id_patient === '' || $type === '' || $id_medecin === '' || $id_creneau === '') {
        echo json_encode(["success" => false, "message" => "champs_manquants"]);
        exit;
    }

    try {
        $db->beginTransaction();

        // 1) Verify patient
        $q = $db->prepare("SELECT id_user
                           FROM user
                           WHERE id_user = :p
                             AND id_role = 'ROLE-PAT'");
        $q->execute([':p' => $id_patient]);
        if (!$q->fetch()) {
            $db->rollBack();
            echo json_encode(["success" => false, "message" => "patient_introuvable"]);
            exit;
        }

        // 2) Lock & verify creneau
        $q = $db->prepare("SELECT statut, id_medecin, date_creneau
                           FROM creneau
                           WHERE id_creneau = :c
                           FOR UPDATE");
        $q->execute([':c' => $id_creneau]);
        $creneau = $q->fetch(PDO::FETCH_ASSOC);

        if (!$creneau) {
            $db->rollBack();
            echo json_encode(["success" => false, "message" => "creneau_introuvable"]);
            exit;
        }

        if (strtolower($creneau['statut']) === 'reserve') {
            $db->rollBack();
            echo json_encode(["success" => false, "message" => "creneau_pris"]);
            exit;
        }

        if ($creneau['id_medecin'] !== $id_medecin) {
            $db->rollBack();
            echo json_encode(["success" => false, "message" => "creneau_medecin_mismatch"]);
            exit;
        }

        $date_rdv = $creneau['date_creneau'] ?: $date;
        $id_rdv   = 'RDV-' . date('YmdHis');

        // 3) Insert RDV
        $sql = "INSERT INTO rendez_vous
                    (id_rdv, date_demande, date_rdv, statut, type_consultation,
                     id_patient, id_creneau, id_medecin)
                VALUES
                    (:id_rdv, :date, :date_rdv, 'en_attente', :type,
                     :patient, :creneau, :medecin)";

        $q = $db->prepare($sql);
        $q->execute([
            ':id_rdv'   => $id_rdv,
            ':date'     => $date,
            ':date_rdv' => $date_rdv,
            ':type'     => $type,
            ':patient'  => $id_patient,
            ':creneau'  => $id_creneau,
            ':medecin'  => $id_medecin
        ]);

        // 4) Mark creneau as reserved
        $q = $db->prepare("UPDATE creneau
                           SET statut = 'reserve'
                           WHERE id_creneau = :c");
        $q->execute([':c' => $id_creneau]);

        $db->commit();

        echo json_encode([
            "success" => true,
            "message" => "RDV créé avec succès",
            "id_rdv"  => $id_rdv
        ]);
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => $e->getMessage()
        ]);
    }

/* ─────────────────────────────────────────────────────────
   ACTION: annuler
   ───────────────────────────────────────────────────────── */
} elseif ($action === 'annuler') {
    $id         = trim($_POST['id'] ?? '');
    $id_patient = trim($_POST['patient'] ?? '');

    if ($id === '' || $id_patient === '') {
        echo json_encode(["success" => false, "message" => "champs_manquants"]);
        exit;
    }

    try {
        $db->beginTransaction();

        $q = $db->prepare("SELECT id_creneau, statut, id_patient
                           FROM rendez_vous
                           WHERE id_rdv = :id
                           FOR UPDATE");
        $q->execute([':id' => $id]);
        $rdv = $q->fetch(PDO::FETCH_ASSOC);

        if (!$rdv) {
            $db->rollBack();
            echo json_encode(["success" => false, "message" => "rdv_introuvable"]);
            exit;
        }

        if ($rdv['id_patient'] !== $id_patient) {
            $db->rollBack();
            echo json_encode(["success" => false, "message" => "acces_refuse"]);
            exit;
        }

        if (strtolower($rdv['statut']) === 'confirme') {
            $db->rollBack();
            echo json_encode(["success" => false, "message" => "rdv_deja_confirme"]);
            exit;
        }

        if (strtolower($rdv['statut']) === 'annule') {
            $db->rollBack();
            echo json_encode(["success" => false, "message" => "rdv_deja_annule"]);
            exit;
        }

        // Delete the RDV (matches your original logic)
        $q = $db->prepare("DELETE FROM rendez_vous WHERE id_rdv = :id");
        $q->execute([':id' => $id]);

        // Free the creneau
        $q = $db->prepare("UPDATE creneau
                           SET statut = 'disponible'
                           WHERE id_creneau = :c");
        $q->execute([':c' => $rdv['id_creneau']]);

        $db->commit();

        echo json_encode(["success" => true, "message" => "RDV annulé"]);
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => $e->getMessage()
        ]);
    }

/* ─────────────────────────────────────────────────────────
   ACTION: modifier
   Modifier un RDV (changer créneau et/ou type) — seulement si en_attente
   ───────────────────────────────────────────────────────── */
} elseif ($action === 'modifier') {
    $id          = trim($_POST['id'] ?? '');
    $id_patient  = trim($_POST['patient'] ?? '');
    $new_creneau = trim($_POST['creneau'] ?? '');
    $new_type    = trim($_POST['type'] ?? '');

    if ($id === '' || $id_patient === '' || $new_creneau === '') {
        echo json_encode(["success" => false, "message" => "champs_manquants"]);
        exit;
    }

    try {
        $db->beginTransaction();

        // 1) Vérifier le RDV existe et appartient au patient
        $q = $db->prepare("SELECT id_creneau, statut, id_patient, id_medecin, type_consultation
                           FROM rendez_vous
                           WHERE id_rdv = :id
                           FOR UPDATE");
        $q->execute([':id' => $id]);
        $rdv = $q->fetch(PDO::FETCH_ASSOC);

        if (!$rdv) {
            $db->rollBack();
            echo json_encode(["success" => false, "message" => "rdv_introuvable"]);
            exit;
        }

        if ($rdv['id_patient'] !== $id_patient) {
            $db->rollBack();
            echo json_encode(["success" => false, "message" => "acces_refuse"]);
            exit;
        }

        // Seuls les RDV en attente peuvent être modifiés
        if (strtolower($rdv['statut']) === 'confirme') {
            $db->rollBack();
            echo json_encode(["success" => false, "message" => "rdv_deja_confirme"]);
            exit;
        }
        if (strtolower($rdv['statut']) === 'annule') {
            $db->rollBack();
            echo json_encode(["success" => false, "message" => "rdv_deja_annule"]);
            exit;
        }

        $old_creneau = $rdv['id_creneau'];

        // 2) Si le créneau change, vérifier le nouveau
        if ($new_creneau !== $old_creneau) {
            // Verrouiller le nouveau créneau
            $q = $db->prepare("SELECT statut, id_medecin, date_creneau
                               FROM creneau
                               WHERE id_creneau = :c
                               FOR UPDATE");
            $q->execute([':c' => $new_creneau]);
            $nouveau = $q->fetch(PDO::FETCH_ASSOC);

            if (!$nouveau) {
                $db->rollBack();
                echo json_encode(["success" => false, "message" => "creneau_introuvable"]);
                exit;
            }

            if (strtolower($nouveau['statut']) === 'reserve') {
                $db->rollBack();
                echo json_encode(["success" => false, "message" => "creneau_pris"]);
                exit;
            }

            // Le nouveau créneau doit être chez le même médecin
            if ($nouveau['id_medecin'] !== $rdv['id_medecin']) {
                $db->rollBack();
                echo json_encode(["success" => false, "message" => "creneau_medecin_mismatch"]);
                exit;
            }

            // 3) Libérer l'ancien créneau
            $q = $db->prepare("UPDATE creneau
                               SET statut = 'disponible'
                               WHERE id_creneau = :c");
            $q->execute([':c' => $old_creneau]);

            // 4) Réserver le nouveau créneau
            $q = $db->prepare("UPDATE creneau
                               SET statut = 'reserve'
                               WHERE id_creneau = :c");
            $q->execute([':c' => $new_creneau]);

            // 5) Mettre à jour le RDV avec le nouveau créneau et la nouvelle date
            $type_to_save = $new_type !== '' ? $new_type : $rdv['type_consultation'];
            $q = $db->prepare("UPDATE rendez_vous
                               SET id_creneau = :c,
                                   date_rdv = :d,
                                   type_consultation = :t
                               WHERE id_rdv = :id");
            $q->execute([
                ':c'  => $new_creneau,
                ':d'  => $nouveau['date_creneau'],
                ':t'  => $type_to_save,
                ':id' => $id
            ]);
        } else {
            // Même créneau : on ne touche qu'au type
            if ($new_type === '') {
                $db->rollBack();
                echo json_encode(["success" => false, "message" => "rien_a_modifier"]);
                exit;
            }
            $q = $db->prepare("UPDATE rendez_vous
                               SET type_consultation = :t
                               WHERE id_rdv = :id");
            $q->execute([':t' => $new_type, ':id' => $id]);
        }

        $db->commit();

        echo json_encode(["success" => true, "message" => "RDV modifié avec succès"]);
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => $e->getMessage()
        ]);
    }

/* ─────────────────────────────────────────────────────────
   ACTION: stats
   ───────────────────────────────────────────────────────── */
} elseif ($action === 'stats') {
    $id_patient = trim($_GET['patient'] ?? '');

    if ($id_patient === '') {
        echo json_encode(["success" => false, "message" => "ID patient manquant"]);
        exit;
    }

    try {
        $q = $db->prepare("SELECT
                COUNT(*)                       AS total,
                SUM(statut = 'confirme')       AS confirmes,
                SUM(statut = 'en_attente')     AS en_attente,
                SUM(statut = 'annule')         AS annules
            FROM rendez_vous
            WHERE id_patient = :p");
        $q->execute([':p' => $id_patient]);

        echo json_encode([
            "success" => true,
            "data"    => $q->fetch(PDO::FETCH_ASSOC)
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => $e->getMessage()
        ]);
    }

} else {
    echo json_encode(["success" => false, "message" => "Action inconnue"]);
}