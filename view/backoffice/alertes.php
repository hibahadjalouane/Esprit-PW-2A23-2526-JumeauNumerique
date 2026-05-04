<?php
/* ═══════════════════════════════════════════════════════════════
   alertes.php — API du Jumeau Numérique
   ─────────────────────────────────────────────────────────────
   Routes :
     GET  alertes.php                       → liste des alertes
     GET  alertes.php?action=medecins_par_specialite&specialite=X
                                            → médecins d'une spé
     POST alertes.php?action=generer_creneaux
          + medecin, date, debut, fin, duree, pause_debut, pause_fin
                                            → INSERT créneaux
   ═══════════════════════════════════════════════════════════════ */

require '../../config.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_REQUEST['action'] ?? 'lire';
$db     = config::getConnexion();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

/* ─────────────────────────────────────────────────────────────
   AUTO-DÉTECTION DU SCHÉMA
   ───────────────────────────────────────────────────────────── */
function colonneExiste(PDO $db, string $table, string $col): bool {
    try {
        $q = $db->query("SHOW COLUMNS FROM `$table` LIKE " . $db->quote($col));
        return (bool) $q->fetch();
    } catch (Exception $e) { return false; }
}
$COL_DATE = colonneExiste($db, 'creneau', 'date_creneau') ? 'date_creneau' : 'date';

/* ═══════════════════════════════════════════════════════════════
   ACTION : medecins_par_specialite
   ═══════════════════════════════════════════════════════════════ */
if ($action === 'medecins_par_specialite') {
    $specialite = trim($_GET['specialite'] ?? '');
    if ($specialite === '') {
        echo json_encode(['success' => false, 'message' => 'specialite_manquante']);
        exit;
    }
    try {
        $sql = "SELECT id_user,
                       CONCAT(COALESCE(Prenom,''),' ',COALESCE(Nom,'')) AS nom_complet,
                       Service
                FROM   user
                WHERE  id_role = 'ROLE-MED'
                  AND  Service = :spe
                ORDER BY Prenom, Nom";
        $q = $db->prepare($sql);
        $q->execute([':spe' => $specialite]);
        echo json_encode([
            'success' => true,
            'data'    => $q->fetchAll(PDO::FETCH_ASSOC)
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

/* ═══════════════════════════════════════════════════════════════
   ACTION : generer_creneaux  (le bouton actionnable)
   ═══════════════════════════════════════════════════════════════ */
if ($action === 'generer_creneaux') {

    $medecin = trim($_POST['medecin']     ?? '');
    $date    = trim($_POST['date']        ?? '');
    $debut   = trim($_POST['debut']       ?? '');
    $fin     = trim($_POST['fin']         ?? '');
    $duree   = (int)($_POST['duree']      ?? 30);
    $pauseD  = trim($_POST['pause_debut'] ?? '');
    $pauseF  = trim($_POST['pause_fin']   ?? '');

    if ($medecin === '' || $date === '' || $debut === '' || $fin === '') {
        echo json_encode(['success' => false, 'message' => 'champs_manquants']);
        exit;
    }
    if ($date < date('Y-m-d')) {
        echo json_encode(['success' => false, 'message' => 'date_passee']);
        exit;
    }
    if ($debut >= $fin) {
        echo json_encode(['success' => false, 'message' => 'horaires_invalides']);
        exit;
    }
    if ($duree < 5 || $duree > 240) {
        echo json_encode(['success' => false, 'message' => 'duree_invalide']);
        exit;
    }

    try {
        $db->beginTransaction();

        $q = $db->prepare("SELECT id_user FROM user
                           WHERE id_user = :m AND id_role = 'ROLE-MED'");
        $q->execute([':m' => $medecin]);
        if (!$q->fetch()) {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => 'medecin_introuvable']);
            exit;
        }

        $heureCourante = strtotime($debut);
        $heureFinPlage = strtotime($fin);
        $pauseDStamp   = $pauseD !== '' ? strtotime($pauseD) : null;
        $pauseFStamp   = $pauseF !== '' ? strtotime($pauseF) : null;

        $crees = 0; $ignores = 0; $erreurs = 0;

        $sqlCheck = "SELECT id_creneau FROM creneau
                     WHERE id_medecin = :m
                       AND `$COL_DATE` = :d
                       AND :hd < heure_fin
                       AND :hf > heure_debut";
        $stmtCheck = $db->prepare($sqlCheck);

        $stmtCheckId = $db->prepare("SELECT id_creneau FROM creneau WHERE id_creneau = :id");

        $sqlInsert = "INSERT INTO creneau
                        (id_creneau, `$COL_DATE`, heure_debut, heure_fin, statut, id_medecin)
                      VALUES (:id, :d, :hd, :hf, 'disponible', :m)";
        $stmtInsert = $db->prepare($sqlInsert);

        while ($heureCourante + $duree * 60 <= $heureFinPlage) {
            $hd = date('H:i:s', $heureCourante);
            $hf = date('H:i:s', $heureCourante + $duree * 60);

            if ($pauseDStamp !== null && $pauseFStamp !== null) {
                if ($heureCourante >= $pauseDStamp && $heureCourante < $pauseFStamp) {
                    $heureCourante += $duree * 60;
                    continue;
                }
            }

            $stmtCheck->execute([
                ':m'  => $medecin,
                ':d'  => $date,
                ':hd' => $hd,
                ':hf' => $hf
            ]);
            if ($stmtCheck->fetch()) {
                $ignores++;
                $heureCourante += $duree * 60;
                continue;
            }

            $tentatives = 0;
            do {
                $idCreneau = 'CR-' . str_pad((string) random_int(1, 999), 3, '0', STR_PAD_LEFT);
                $stmtCheckId->execute([':id' => $idCreneau]);
                $tentatives++;
            } while ($stmtCheckId->fetch() && $tentatives < 50);

            if ($tentatives >= 50) {
                $idCreneau = 'CR-' . substr((string) time(), -6);
            }

            try {
                $stmtInsert->execute([
                    ':id' => $idCreneau,
                    ':d'  => $date,
                    ':hd' => $hd,
                    ':hf' => $hf,
                    ':m'  => $medecin
                ]);
                $crees++;
            } catch (Exception $eIns) {
                $erreurs++;
            }

            $heureCourante += $duree * 60;
        }

        $db->commit();

        echo json_encode([
            'success'  => true,
            'message'  => "{$crees} créneau(x) créé(s)",
            'crees'    => $crees,
            'ignores'  => $ignores,
            'erreurs'  => $erreurs
        ]);

    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

/* ═══════════════════════════════════════════════════════════════
   ACTION : rdv_du_patient  (pour la modale "demander confirmation")
   ═══════════════════════════════════════════════════════════════ */
if ($action === 'rdv_du_patient') {
    $patient = trim($_GET['patient'] ?? '');
    if ($patient === '') {
        echo json_encode(['success' => false, 'message' => 'patient_manquant']);
        exit;
    }
    try {
        $sql = "SELECT rv.id_rdv, rv.date_rdv, rv.statut, rv.type_consultation,
                       CONCAT(COALESCE(u.Prenom,''),' ',COALESCE(u.Nom,'')) AS medecin,
                       u.Service AS specialite
                FROM rendez_vous rv
                LEFT JOIN user u ON u.id_user = rv.id_medecin
                WHERE rv.id_patient = :p
                  AND LOWER(rv.statut) IN ('en_attente','confirme')
                ORDER BY rv.date_rdv ASC";
        $q = $db->prepare($sql);
        $q->execute([':p' => $patient]);
        echo json_encode([
            'success' => true,
            'data'    => $q->fetchAll(PDO::FETCH_ASSOC)
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

/* ═══════════════════════════════════════════════════════════════
   ACTION : marquer_a_confirmer
   POST : ids[] = ['RDV-001', 'RDV-002', ...]
   → UPDATE rendez_vous SET statut='a_confirmer' WHERE id_rdv IN (...)
   ═══════════════════════════════════════════════════════════════ */
if ($action === 'marquer_a_confirmer') {
    $ids = $_POST['ids'] ?? [];
    if (!is_array($ids) || empty($ids)) {
        echo json_encode(['success' => false, 'message' => 'aucun_rdv_selectionne']);
        exit;
    }
    try {
        $db->beginTransaction();
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "UPDATE rendez_vous SET statut = 'a_confirmer'
                WHERE id_rdv IN ($placeholders)";
        $q = $db->prepare($sql);
        $q->execute(array_values($ids));
        $nb = $q->rowCount();
        $db->commit();
        echo json_encode([
            'success' => true,
            'message' => "{$nb} RDV marqué(s) à confirmer",
            'nb'      => $nb
        ]);
    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

/* ═══════════════════════════════════════════════════════════════
   ACTION : rdv_a_facturer  (preview pour la modale facture)
   ═══════════════════════════════════════════════════════════════ */
if ($action === 'rdv_a_facturer') {
    try {
        $sql = "SELECT rv.id_rdv, rv.date_rdv, rv.type_consultation,
                       rv.id_patient,
                       CONCAT(COALESCE(u.Prenom,''),' ',COALESCE(u.Nom,'')) AS patient
                FROM   rendez_vous rv
                LEFT JOIN user u ON u.id_user = rv.id_patient
                LEFT JOIN facture f ON f.id_rdv = rv.id_rdv
                WHERE  LOWER(rv.statut) = 'confirme'
                  AND  f.id_facture IS NULL
                ORDER BY rv.date_rdv DESC";
        $q = $db->query($sql);
        echo json_encode([
            'success' => true,
            'data'    => $q->fetchAll(PDO::FETCH_ASSOC)
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

/* ═══════════════════════════════════════════════════════════════
   ACTION : regenerer_factures
   POST : montant (DT)
   → INSERT une facture pour chaque RDV confirmé sans facture
   ═══════════════════════════════════════════════════════════════ */
if ($action === 'regenerer_factures') {
    $montant = (float)($_POST['montant'] ?? 0);
    if ($montant <= 0 || $montant > 10000) {
        echo json_encode(['success' => false, 'message' => 'montant_invalide']);
        exit;
    }

    try {
        $db->beginTransaction();

        /* Récupérer tous les RDV confirmés sans facture */
        $sql = "SELECT rv.id_rdv, rv.id_patient
                FROM   rendez_vous rv
                LEFT JOIN facture f ON f.id_rdv = rv.id_rdv
                WHERE  LOWER(rv.statut) = 'confirme'
                  AND  f.id_facture IS NULL";
        $rdvs = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rdvs)) {
            $db->rollBack();
            echo json_encode(['success' => false, 'message' => 'aucun_rdv_a_facturer']);
            exit;
        }

        /* Trouver un id_type_paiement valide (le 1er existant) */
        $typeRow = $db->query("SELECT id_type FROM type_paiement LIMIT 1")
                      ->fetch(PDO::FETCH_ASSOC);
        if (!$typeRow) {
            /* Fallback : créer un type_paiement par défaut */
            $db->exec("INSERT INTO type_paiement (id_type, nom_type, description, montant)
                       VALUES (1, 'Especes', 'Paiement par defaut', 0)");
            $idType = 1;
        } else {
            $idType = $typeRow['id_type'];
        }

        $stmt = $db->prepare("INSERT INTO facture
                                (id_facture, montant, statut, date_facture,
                                 id_rdv, id_type_paiement, id_patient)
                              VALUES (:idf, :m, 'impayee', CURDATE(),
                                      :rdv, :idt, :pat)");

        $crees = 0;
        $erreurs = 0;
        foreach ($rdvs as $rdv) {
            /* Générer un id_facture unique (entier, ta colonne est int(8)) */
            $tentatives = 0;
            do {
                $idFact = random_int(1, 99999999);
                $check = $db->prepare("SELECT id_facture FROM facture WHERE id_facture = :id");
                $check->execute([':id' => $idFact]);
                $tentatives++;
            } while ($check->fetch() && $tentatives < 50);

            try {
                $stmt->execute([
                    ':idf' => $idFact,
                    ':m'   => $montant,
                    ':rdv' => $rdv['id_rdv'],
                    ':idt' => $idType,
                    ':pat' => $rdv['id_patient']
                ]);
                $crees++;
            } catch (Exception $e) {
                $erreurs++;
            }
        }

        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => "{$crees} facture(s) générée(s)",
            'crees'   => $crees,
            'erreurs' => $erreurs,
            'total'   => $crees * $montant
        ]);

    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

/* ═══════════════════════════════════════════════════════════════
   ACTION : creneaux_morts  (preview avant suppression)
   GET : heure (HH:MM)
   ═══════════════════════════════════════════════════════════════ */
if ($action === 'creneaux_morts') {
    $heure = trim($_GET['heure'] ?? '');
    if ($heure === '') {
        echo json_encode(['success' => false, 'message' => 'heure_manquante']);
        exit;
    }
    try {
        $sql = "SELECT c.id_creneau, c.`$COL_DATE` AS date_creneau,
                       c.heure_debut, c.heure_fin, c.statut,
                       CONCAT(COALESCE(u.Prenom,''),' ',COALESCE(u.Nom,'')) AS medecin
                FROM   creneau c
                LEFT JOIN user u ON u.id_user = c.id_medecin
                WHERE  TIME_FORMAT(c.heure_debut,'%H:%i') = :h
                  AND  LOWER(c.statut) = 'disponible'
                  AND  c.`$COL_DATE` >= DATE_SUB(CURDATE(), INTERVAL 45 DAY)
                ORDER BY c.`$COL_DATE` DESC";
        $q = $db->prepare($sql);
        $q->execute([':h' => $heure]);
        echo json_encode([
            'success' => true,
            'data'    => $q->fetchAll(PDO::FETCH_ASSOC)
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

/* ═══════════════════════════════════════════════════════════════
   ACTION : supprimer_creneaux_morts
   POST : ids[] = ['CR-M01', 'CR-M02', ...]
   ═══════════════════════════════════════════════════════════════ */
if ($action === 'supprimer_creneaux_morts') {
    $ids = $_POST['ids'] ?? [];
    if (!is_array($ids) || empty($ids)) {
        echo json_encode(['success' => false, 'message' => 'aucun_creneau_selectionne']);
        exit;
    }
    try {
        $db->beginTransaction();
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        /* Sécurité : ne supprimer QUE les créneaux disponibles (jamais réservés) */
        $sql = "DELETE FROM creneau
                WHERE id_creneau IN ($placeholders)
                  AND LOWER(statut) = 'disponible'";
        $q = $db->prepare($sql);
        $q->execute(array_values($ids));
        $nb = $q->rowCount();
        $db->commit();
        echo json_encode([
            'success' => true,
            'message' => "{$nb} créneau(x) supprimé(s)",
            'nb'      => $nb
        ]);
    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

/* ═══════════════════════════════════════════════════════════════
   ACTION : rdv_redistribuables  (preview pour la modale redistribution)
   GET : medecin (id_user), date (YYYY-MM-DD)
   ═══════════════════════════════════════════════════════════════ */
if ($action === 'rdv_redistribuables') {
    $medecin = trim($_GET['medecin'] ?? '');
    $date    = trim($_GET['date'] ?? '');
    if ($medecin === '' || $date === '') {
        echo json_encode(['success' => false, 'message' => 'parametres_manquants']);
        exit;
    }
    try {
        $sql = "SELECT rv.id_rdv, rv.type_consultation, rv.date_rdv,
                       rv.id_creneau, rv.id_patient,
                       c.heure_debut, c.heure_fin,
                       CONCAT(COALESCE(u.Prenom,''),' ',COALESCE(u.Nom,'')) AS patient
                FROM   rendez_vous rv
                LEFT JOIN creneau c ON c.id_creneau = rv.id_creneau
                LEFT JOIN user u    ON u.id_user    = rv.id_patient
                WHERE  rv.id_medecin = :m
                  AND  rv.date_rdv = :d
                  AND  LOWER(rv.type_consultation) NOT LIKE '%urgence%'
                ORDER BY c.heure_debut ASC";
        $q = $db->prepare($sql);
        $q->execute([':m' => $medecin, ':d' => $date]);
        echo json_encode([
            'success' => true,
            'data'    => $q->fetchAll(PDO::FETCH_ASSOC)
        ]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

/* ═══════════════════════════════════════════════════════════════
   ACTION : redistribuer_rdv
   POST : ids[]            = liste des id_rdv à reporter
          nouveau_creneau  = id_creneau cible (optionnel)
          nouvelle_date    = YYYY-MM-DD pour libération du créneau actuel
   → libère les anciens créneaux + met les RDV en 'a_reporter'
   ═══════════════════════════════════════════════════════════════ */
if ($action === 'redistribuer_rdv') {
    $ids = $_POST['ids'] ?? [];
    if (!is_array($ids) || empty($ids)) {
        echo json_encode(['success' => false, 'message' => 'aucun_rdv_selectionne']);
        exit;
    }
    try {
        $db->beginTransaction();

        /* 1. Récupérer les créneaux des RDV à libérer */
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $db->prepare("SELECT id_creneau FROM rendez_vous
                              WHERE id_rdv IN ($placeholders)");
        $stmt->execute(array_values($ids));
        $creneauxALiberer = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'id_creneau');

        /* 2. Mettre les RDV en statut 'a_reporter' */
        $stmt = $db->prepare("UPDATE rendez_vous SET statut = 'a_reporter'
                              WHERE id_rdv IN ($placeholders)");
        $stmt->execute(array_values($ids));
        $nbRdv = $stmt->rowCount();

        /* 3. Libérer les créneaux correspondants */
        $nbCreneaux = 0;
        if (!empty($creneauxALiberer)) {
            $ph2 = implode(',', array_fill(0, count($creneauxALiberer), '?'));
            $stmt = $db->prepare("UPDATE creneau SET statut = 'disponible'
                                  WHERE id_creneau IN ($ph2)");
            $stmt->execute($creneauxALiberer);
            $nbCreneaux = $stmt->rowCount();
        }

        $db->commit();
        echo json_encode([
            'success'    => true,
            'message'    => "{$nbRdv} RDV reporté(s), {$nbCreneaux} créneau(x) libéré(s)",
            'nb_rdv'     => $nbRdv,
            'nb_creneaux'=> $nbCreneaux
        ]);
    } catch (Exception $e) {
        if ($db->inTransaction()) $db->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

/* ═══════════════════════════════════════════════════════════════
   ACTION : profil_medecin  (modale profil détaillé)
   GET : id (id_user du médecin)
   ═══════════════════════════════════════════════════════════════ */
if ($action === 'profil_medecin') {
    $id = trim($_GET['id'] ?? '');
    if ($id === '') {
        echo json_encode(['success' => false, 'message' => 'id_manquant']);
        exit;
    }

    try {
        /* 1) Infos générales du médecin */
        $q = $db->prepare("SELECT id_user, Nom, Prenom, Service, Email, CIN
                           FROM user WHERE id_user = :id AND id_role = 'ROLE-MED'");
        $q->execute([':id' => $id]);
        $medecin = $q->fetch(PDO::FETCH_ASSOC);
        if (!$medecin) {
            echo json_encode(['success' => false, 'message' => 'medecin_introuvable']);
            exit;
        }

        /* 2) Stats globales sur 30 derniers jours */
        $sql = "SELECT
                    COUNT(*) AS nb_total,
                    SUM(CASE WHEN LOWER(statut)='reserve' THEN 1 ELSE 0 END) AS nb_reserves,
                    SUM(CASE WHEN LOWER(statut)='disponible' THEN 1 ELSE 0 END) AS nb_libres
                FROM creneau
                WHERE id_medecin = :id
                  AND `$COL_DATE` >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                  AND `$COL_DATE` <= CURDATE()";
        $q = $db->prepare($sql);
        $q->execute([':id' => $id]);
        $stats = $q->fetch(PDO::FETCH_ASSOC);
        $taux  = $stats['nb_total'] > 0
               ? round(($stats['nb_reserves'] / $stats['nb_total']) * 100)
               : 0;

        /* 3) Moyenne hôpital pour la même période et même spécialité */
        $sql = "SELECT
                    AVG(taux_par_medecin) AS moyenne
                FROM (
                    SELECT (SUM(CASE WHEN LOWER(c.statut)='reserve' THEN 1 ELSE 0 END) * 1.0
                          / NULLIF(COUNT(*), 0)) * 100 AS taux_par_medecin
                    FROM creneau c
                    JOIN user u ON u.id_user = c.id_medecin
                    WHERE u.Service = :spec
                      AND c.`$COL_DATE` >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                      AND c.`$COL_DATE` <= CURDATE()
                    GROUP BY c.id_medecin
                    HAVING COUNT(*) >= 3
                ) sous";
        $q = $db->prepare($sql);
        $q->execute([':spec' => $medecin['Service']]);
        $moyenneSpe = round((float)($q->fetchColumn() ?: 0));

        /* 4) Évolution sur 30 jours, regroupée par tranches de 5 jours */
        $sql = "SELECT
                    FLOOR(DATEDIFF(CURDATE(), `$COL_DATE`) / 5) AS tranche,
                    COUNT(*) AS total,
                    SUM(CASE WHEN LOWER(statut)='reserve' THEN 1 ELSE 0 END) AS reserves
                FROM creneau
                WHERE id_medecin = :id
                  AND `$COL_DATE` >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                  AND `$COL_DATE` <= CURDATE()
                GROUP BY tranche
                ORDER BY tranche DESC";
        $q = $db->prepare($sql);
        $q->execute([':id' => $id]);
        $evolution = [];
        foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $tx = $row['total'] > 0 ? round(($row['reserves'] / $row['total']) * 100) : 0;
            $evolution[] = [
                'periode'  => "J-".((int)$row['tranche'] * 5),
                'total'    => (int)$row['total'],
                'reserves' => (int)$row['reserves'],
                'taux'     => $tx
            ];
        }
        $evolution = array_reverse($evolution); // chronologique

        /* 5) Créneaux libres à venir (7 prochains jours) */
        $sql = "SELECT id_creneau, `$COL_DATE` AS date_creneau, heure_debut, heure_fin
                FROM creneau
                WHERE id_medecin = :id
                  AND LOWER(statut) = 'disponible'
                  AND `$COL_DATE` >= CURDATE()
                  AND `$COL_DATE` <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                ORDER BY `$COL_DATE` ASC, heure_debut ASC
                LIMIT 5";
        $q = $db->prepare($sql);
        $q->execute([':id' => $id]);
        $creneauxLibres = $q->fetchAll(PDO::FETCH_ASSOC);

        /* 6) Suggestions intelligentes selon le contexte */
        $suggestions = [];
        if ($taux < 15) {
            $suggestions[] = [
                'icone'  => 'megaphone',
                'titre'  => 'Promouvoir auprès des patients récents',
                'detail' => "Recommander {$medecin['Prenom']} aux patients ayant déjà consulté en {$medecin['Service']}."
            ];
        }
        if ($moyenneSpe > $taux + 15) {
            $suggestions[] = [
                'icone'  => 'trending-down',
                'titre'  => "Performance inférieure à la moyenne ({$moyenneSpe}%)",
                'detail' => "Les autres médecins de la spécialité {$medecin['Service']} ont un taux moyen de {$moyenneSpe}%. Étudier les causes (horaires, image, expérience)."
            ];
        }
        if ($stats['nb_libres'] >= 5) {
            $suggestions[] = [
                'icone'  => 'scissors',
                'titre'  => 'Réduire le nombre de créneaux',
                'detail' => "{$stats['nb_libres']} créneaux libres sur les 30 derniers jours. Diminuer l'offre pour optimiser la perception de rareté."
            ];
        }
        if (count($creneauxLibres) > 0) {
            $suggestions[] = [
                'icone'  => 'star',
                'titre'  => 'Mettre en avant sur la page d\'accueil',
                'detail' => "Afficher ses prochains créneaux libres en bannière du portail patient."
            ];
        }
        if (empty($suggestions)) {
            $suggestions[] = [
                'icone'  => 'check',
                'titre'  => 'Aucune action urgente',
                'detail' => 'Le profil semble équilibré, surveiller à nouveau dans 7 jours.'
            ];
        }

        echo json_encode([
            'success'        => true,
            'medecin'        => $medecin,
            'stats'          => [
                'nb_total'    => (int)$stats['nb_total'],
                'nb_reserves' => (int)$stats['nb_reserves'],
                'nb_libres'   => (int)$stats['nb_libres'],
                'taux'        => $taux,
                'moyenne_spe' => $moyenneSpe
            ],
            'evolution'      => $evolution,
            'creneaux_libres'=> $creneauxLibres,
            'suggestions'    => $suggestions
        ], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

/* ═══════════════════════════════════════════════════════════════
   ACTION par défaut : LIRE LES ALERTES
   ═══════════════════════════════════════════════════════════════ */

$alertes = [];
$erreurs = [];

function pushAlerte(array &$alertes, string $niveau, string $categorie,
                    string $titre, string $detail, ?string $action = null,
                    ?array $payload = null): void {
    $alertes[] = [
        'niveau'    => $niveau,
        'categorie' => $categorie,
        'titre'     => $titre,
        'detail'    => $detail,
        'action'    => $action,
        'payload'   => $payload,
        'timestamp' => date('Y-m-d H:i:s')
    ];
}

function executer(string $nom, callable $fn, array &$erreurs): void {
    try { $fn(); }
    catch (Exception $e) {
        $erreurs[] = ['detecteur' => $nom, 'erreur' => $e->getMessage()];
    }
}

/* 1. SURCHARGE MÉDECIN — ACTIONNABLE */
executer('surcharge_medecin', function () use ($db, $COL_DATE, &$alertes) {
    $sql = "
        SELECT  c.id_medecin, c.`$COL_DATE` AS jour,
                CONCAT(COALESCE(u.Prenom,''),' ',COALESCE(u.Nom,'')) AS medecin,
                u.Service AS specialite,
                COUNT(*) AS nb_reserves
        FROM    creneau c JOIN user u ON u.id_user = c.id_medecin
        WHERE   LOWER(c.statut) = 'reserve'
          AND   c.`$COL_DATE` >= CURDATE()
          AND   c.`$COL_DATE` <= DATE_ADD(CURDATE(), INTERVAL 14 DAY)
        GROUP BY c.id_medecin, c.`$COL_DATE`
        HAVING  nb_reserves >= 8
        ORDER BY nb_reserves DESC LIMIT 3
    ";
    foreach ($db->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $jour = date('d/m', strtotime($r['jour']));
        $retard = (int)$r['nb_reserves'] * 5;
        pushAlerte($alertes, 'critique', 'Surcharge médecin',
            trim($r['medecin']) . " surchargé(e) le {$jour}",
            "{$r['nb_reserves']} RDV programmés · risque retard ≈ {$retard} min",
            "Redistribuer 2-3 RDV non urgents",
            [
                'type'         => 'redistribuer_rdv',
                'id_medecin'   => $r['id_medecin'],
                'medecin_nom'  => trim($r['medecin']),
                'date'         => $r['jour'],
                'nb_reserves'  => (int)$r['nb_reserves']
            ]);
    }
}, $erreurs);

/* 2. PATIENT À RISQUE — ACTIONNABLE */
executer('patient_a_risque', function () use ($db, &$alertes) {
    $sql = "
        SELECT  rv.id_patient,
                CONCAT(COALESCE(u.Prenom,''),' ',COALESCE(u.Nom,'')) AS patient,
                COUNT(*) AS nb_annul,
                (SELECT COUNT(*) FROM rendez_vous WHERE id_patient = rv.id_patient) AS nb_total
        FROM    rendez_vous rv LEFT JOIN user u ON u.id_user = rv.id_patient
        WHERE   LOWER(rv.statut) IN ('annule','refuse','no_show')
        GROUP BY rv.id_patient
        HAVING  nb_annul >= 2
        ORDER BY nb_annul DESC LIMIT 3
    ";
    foreach ($db->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $taux = $r['nb_total'] > 0 ? round(($r['nb_annul'] / $r['nb_total']) * 100) : 0;
        $nom = trim($r['patient']) !== '' ? trim($r['patient']) : ('Patient #'.$r['id_patient']);
        pushAlerte($alertes, 'attention', 'Comportement patient',
            "{$nom} : {$r['nb_annul']} annulations détectées",
            "Taux d'annulation {$taux}% · pattern de no-show probable",
            "Demander confirmation 24 h avant",
            [
                'type'        => 'marquer_a_confirmer',
                'id_patient'  => $r['id_patient'],
                'patient_nom' => $nom,
                'nb_annul'    => (int)$r['nb_annul']
            ]);
    }
}, $erreurs);

/* 3. ENGORGEMENT SPÉCIALITÉ — ACTIONNABLE */
executer('engorgement_specialite', function () use ($db, $COL_DATE, &$alertes) {
    $sql = "
        SELECT  u.Service AS specialite,
                MIN(c.`$COL_DATE`) AS prochain_dispo,
                COUNT(*) AS nb_dispo_total
        FROM    creneau c JOIN user u ON u.id_user = c.id_medecin
        WHERE   LOWER(c.statut) = 'disponible'
          AND   c.`$COL_DATE` >= CURDATE()
          AND   u.Service IS NOT NULL AND u.Service <> ''
        GROUP BY u.Service
        HAVING  prochain_dispo > DATE_ADD(CURDATE(), INTERVAL 7 DAY)
           OR  nb_dispo_total < 3
        LIMIT 3
    ";
    foreach ($db->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $delai = $r['prochain_dispo']
               ? (int)((strtotime($r['prochain_dispo']) - time()) / 86400)
               : null;
        $detail = $delai !== null
                ? "Prochain créneau dans {$delai} jours · {$r['nb_dispo_total']} créneaux libres"
                : "Aucun créneau libre · {$r['nb_dispo_total']} disponibles total";
        pushAlerte($alertes, 'critique', 'Engorgement spécialité',
            "Spécialité « {$r['specialite']} » saturée",
            $detail,
            "Ouvrir des créneaux supplémentaires",
            ['type' => 'generer_creneaux', 'specialite' => $r['specialite']]);
    }
}, $erreurs);

/* 4. MÉDECIN SOUS-UTILISÉ — ACTIONNABLE (modale profil détaillé) */
executer('medecin_sous_utilise', function () use ($db, $COL_DATE, &$alertes) {
    /* On enveloppe dans une sous-requête pour pouvoir utiliser
       les alias dans HAVING/ORDER BY (compatibilité large MySQL). */
    $sql = "
        SELECT * FROM (
            SELECT  c.id_medecin,
                    CONCAT(COALESCE(u.Prenom,''),' ',COALESCE(u.Nom,'')) AS medecin,
                    u.Service AS specialite,
                    COUNT(*) AS nb_total,
                    SUM(CASE WHEN LOWER(c.statut)='reserve' THEN 1 ELSE 0 END) AS nb_reserves
            FROM    creneau c JOIN user u ON u.id_user = c.id_medecin
            WHERE   c.`$COL_DATE` >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
              AND   c.`$COL_DATE` <= CURDATE()
            GROUP BY c.id_medecin
        ) sous
        WHERE  nb_total >= 5 AND (nb_reserves * 1.0 / nb_total) < 0.30
        ORDER BY (nb_reserves * 1.0 / nb_total) ASC LIMIT 2
    ";
    foreach ($db->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $taux = round(($r['nb_reserves'] / $r['nb_total']) * 100);
        pushAlerte($alertes, 'optimisation', 'Sous-utilisation',
            trim($r['medecin']) . " sous-utilisé(e) (taux {$taux}%)",
            "{$r['nb_reserves']}/{$r['nb_total']} créneaux occupés sur 30 jours",
            "Promouvoir cette spécialité",
            [
                'type'        => 'voir_profil_medecin',
                'id_medecin'  => $r['id_medecin'],
                'medecin_nom' => trim($r['medecin']),
                'specialite'  => $r['specialite'],
                'taux'        => $taux,
                'nb_total'    => (int)$r['nb_total'],
                'nb_reserves' => (int)$r['nb_reserves']
            ]);
    }
}, $erreurs);

/* 5. CRÉNEAU MORT — ACTIONNABLE */
executer('creneau_mort', function () use ($db, $COL_DATE, &$alertes) {
    $sql = "
        SELECT  TIME_FORMAT(c.heure_debut,'%H:%i') AS heure,
                COUNT(*) AS nb_total,
                SUM(CASE WHEN LOWER(c.statut)='reserve' THEN 1 ELSE 0 END) AS nb_reserves
        FROM    creneau c
        WHERE   c.`$COL_DATE` >= DATE_SUB(CURDATE(), INTERVAL 45 DAY)
          AND   c.`$COL_DATE` <= CURDATE()
        GROUP BY c.heure_debut
        HAVING  nb_total >= 3 AND nb_reserves = 0
        ORDER BY nb_total DESC LIMIT 2
    ";
    foreach ($db->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $r) {
        pushAlerte($alertes, 'optimisation', 'Créneau mort',
            "Créneau {$r['heure']} jamais réservé",
            "{$r['nb_total']} occurrences sur 45 jours · taux 0%",
            "Supprimer ou repositionner",
            [
                'type'     => 'supprimer_creneaux_morts',
                'heure'    => $r['heure'],
                'nb_total' => (int)$r['nb_total']
            ]);
    }
}, $erreurs);

/* 6. TENDANCE SAISONNIÈRE — ACTIONNABLE en cas de hausse */
executer('tendance_saisonniere', function () use ($db, &$alertes) {
    $sql = "
        SELECT  u.Service AS specialite,
                SUM(CASE WHEN rv.date_demande >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                         THEN 1 ELSE 0 END) AS recent,
                SUM(CASE WHEN rv.date_demande >= DATE_SUB(CURDATE(), INTERVAL 60 DAY)
                          AND rv.date_demande <  DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                         THEN 1 ELSE 0 END) AS precedent
        FROM    rendez_vous rv JOIN user u ON u.id_user = rv.id_medecin
        WHERE   u.Service IS NOT NULL AND u.Service <> ''
          AND   rv.date_demande >= DATE_SUB(CURDATE(), INTERVAL 60 DAY)
        GROUP BY u.Service
        HAVING  precedent >= 3
    ";
    foreach ($db->query($sql)->fetchAll(PDO::FETCH_ASSOC) as $r) {
        if ($r['precedent'] <= 0) continue;
        $variation = (($r['recent'] - $r['precedent']) / $r['precedent']) * 100;
        if (abs($variation) >= 25) {
            $sens = $variation > 0 ? '+' : '';
            $pct  = round($variation);
            $reco = $variation > 0
                  ? "Ouvrir des créneaux supplémentaires"
                  : "Réduire l'offre · proposer aux confrères";
            pushAlerte($alertes, 'prediction', 'Tendance saisonnière',
                "{$sens}{$pct}% demandes en « {$r['specialite']} »",
                "Évolution sur 30 jours · variation significative", $reco,
                $variation > 0
                    ? ['type' => 'generer_creneaux', 'specialite' => $r['specialite']]
                    : null);
        }
    }
}, $erreurs);

/* 7. RDV FANTÔME — ACTIONNABLE */
executer('rdv_fantome', function () use ($db, &$alertes) {
    $sql = "SELECT COUNT(*) AS nb FROM rendez_vous rv
            LEFT JOIN facture f ON f.id_rdv = rv.id_rdv
            WHERE LOWER(rv.statut) = 'confirme' AND f.id_facture IS NULL";
    $nb = (int) $db->query($sql)->fetchColumn();
    if ($nb > 0) {
        pushAlerte($alertes, 'attention', 'Incohérence facturation',
            "{$nb} RDV confirmé(s) sans facture",
            "Le flux RDV → facture présente une rupture de continuité",
            "Lancer la régénération automatique",
            [
                'type'  => 'regenerer_factures',
                'nb'    => $nb
            ]);
    }
}, $erreurs);

/* Tri + score */
$ordre = ['critique' => 0, 'attention' => 1, 'prediction' => 2, 'optimisation' => 3];
usort($alertes, fn($a, $b) => $ordre[$a['niveau']] <=> $ordre[$b['niveau']]);

$compteurs = ['critique' => 0, 'attention' => 0, 'prediction' => 0, 'optimisation' => 0];
foreach ($alertes as $a) $compteurs[$a['niveau']]++;

$score = 100 - ($compteurs['critique'] * 15) - ($compteurs['attention'] * 7)
             - ($compteurs['prediction'] * 3) - ($compteurs['optimisation'] * 2);
$score = max(0, min(100, $score));

echo json_encode([
    'success'   => true,
    'alertes'   => $alertes,
    'compteurs' => $compteurs,
    'total'     => count($alertes),
    'score'     => $score,
    'horodate'  => date('H:i:s'),
    'erreurs'   => $erreurs,
], JSON_UNESCAPED_UNICODE);