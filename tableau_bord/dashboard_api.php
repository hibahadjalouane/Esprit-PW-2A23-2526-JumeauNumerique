<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once __DIR__ . '/../config.php';
$pdo = config::getConnexion();
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$debugErrors = [];

function safeScalar($pdo, $sql, $params = [], $label = '') {
    global $debugErrors;
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_NUM);
        return $row ? (int)$row[0] : 0;
    } catch (Throwable $e) {
        if ($label) $debugErrors[] = $label . ' : ' . $e->getMessage();
        return 0;
    }
}

function safeRows($pdo, $sql, $params = [], $label = '') {
    global $debugErrors;
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {
        if ($label) $debugErrors[] = $label . ' : ' . $e->getMessage();
        return [];
    }
}

function tableExists($pdo, $table) {
    try {
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        return (bool)$stmt->fetchColumn();
    } catch (Throwable $e) { return false; }
}

function columnExists($pdo, $table, $column) {
    try {
        $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
        $stmt->execute([$column]);
        return (bool)$stmt->fetchColumn();
    } catch (Throwable $e) { return false; }
}

$today    = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$month    = date('Y-m');

// Noms de colonnes adaptatifs pour éviter que l'activité récente casse tout le dashboard.
$hasFacture   = tableExists($pdo, 'facture');
$hasRdv       = tableExists($pdo, 'rendez_vous');
$hasAdmission = tableExists($pdo, 'admission');
$hasDossier   = tableExists($pdo, 'dossier_medical');
$hasRessource = tableExists($pdo, 'ressources');
$hasPanne     = tableExists($pdo, 'panne');
$hasUser      = tableExists($pdo, 'user');
$hasSalle     = tableExists($pdo, 'salle');

// ── KPI ──────────────────────────────────────────────────────────────────────
$factures_mois = $hasFacture ? safeScalar($pdo,
    "SELECT COUNT(*) FROM facture WHERE DATE_FORMAT(date_facture,'%Y-%m') = ?", [$month], 'factures_mois') : 0;
$factures_attente = $hasFacture ? safeScalar($pdo,
    "SELECT COUNT(*) FROM facture WHERE LOWER(REPLACE(statut, ' ', '_')) IN ('non_payee','non_payé','non_payée','en_attente','impayee','impayé','impayée')", [], 'factures_attente') : 0;

$rdv_aujourd_hui = $hasRdv ? safeScalar($pdo,
    "SELECT COUNT(*) FROM rendez_vous WHERE date_rdv = ?", [$today], 'rdv_aujourd_hui') : 0;
$rdv_avenir = $hasRdv ? safeScalar($pdo,
    "SELECT COUNT(*) FROM rendez_vous WHERE date_rdv >= ?", [$tomorrow], 'rdv_avenir') : 0;
$rdv_confirmes = $hasRdv ? safeScalar($pdo,
    "SELECT COUNT(*) FROM rendez_vous WHERE LOWER(REPLACE(statut, 'é', 'e')) IN ('confirme','confirmé')", [], 'rdv_confirmes') : 0;
$rdv_attente = $hasRdv ? safeScalar($pdo,
    "SELECT COUNT(*) FROM rendez_vous WHERE LOWER(REPLACE(statut, ' ', '_')) IN ('en_attente','a_confirmer','à_confirmer')", [], 'rdv_attente') : 0;
$rdv_refuses = $hasRdv ? safeScalar($pdo,
    "SELECT COUNT(*) FROM rendez_vous WHERE LOWER(REPLACE(statut, 'é', 'e')) IN ('refuse','refusé','annule','annulé','rejete','rejeté')", [], 'rdv_refuses') : 0;

$admissions_total = $hasAdmission ? safeScalar($pdo, "SELECT COUNT(*) FROM admission", [], 'admissions_total') : 0;
$admissions_aujourd_hui = $hasAdmission ? safeScalar($pdo,
    "SELECT COUNT(*) FROM admission WHERE date_arrive_relle = ?", [$today], 'admissions_aujourd_hui') : 0;

$ressources_en_service = $hasRessource ? safeScalar($pdo,
    "SELECT COUNT(*) FROM ressources WHERE LOWER(Statut) IN ('en service','service','disponible')", [], 'ressources_en_service') : 0;
$ressources_en_panne = $hasRessource ? safeScalar($pdo,
    "SELECT COUNT(*) FROM ressources WHERE LOWER(Statut) IN ('en panne','panne','maintenance')", [], 'ressources_en_panne') : 0;

// ── MODULES ───────────────────────────────────────────────────────────────────
$mod_factures   = $factures_attente;
$mod_rdv        = $rdv_aujourd_hui;
$mod_admissions = $admissions_total;
$mod_dossiers   = $hasDossier ? safeScalar($pdo, "SELECT COUNT(*) FROM dossier_medical", [], 'mod_dossiers') : 0;
$mod_ressources = $ressources_en_service;
$mod_medecins   = $hasUser ? safeScalar($pdo, "SELECT COUNT(*) FROM user WHERE id_role = 3", [], 'mod_medecins') : 0;

// ── ACTIVITES RECENTES ────────────────────────────────────────────────────────
$activites = [];

if ($hasFacture) {
    $joinUser = $hasUser ? "LEFT JOIN user u ON u.id_user = f.id_patient" : "";
    $selectUser = $hasUser ? ", u.Nom, u.Prenom" : ", '' AS Nom, '' AS Prenom";
    $facs = safeRows($pdo,
        "SELECT f.id_facture, f.date_facture, f.montant, f.statut $selectUser
         FROM facture f $joinUser
         ORDER BY f.date_facture DESC LIMIT 5", [], 'activites_factures');
    foreach ($facs as $r) {
        $patient = trim(($r['Prenom'] ?? '') . ' ' . ($r['Nom'] ?? '')) ?: 'patient inconnu';
        $statut = strtolower(str_replace([' ', 'é', 'è'], ['_', 'e', 'e'], $r['statut'] ?? ''));
        if (in_array($statut, ['payee', 'paye'], true)) {
            $label = "Paiement reçu — Facture #{$r['id_facture']} — {$r['montant']} TND";
        } elseif (in_array($statut, ['en_retard', 'retard'], true)) {
            $label = "Facture #{$r['id_facture']} en retard — Patient : $patient";
        } else {
            $label = "Facture #{$r['id_facture']} créée — Patient : $patient — {$r['montant']} TND";
        }
        $activites[] = ['label' => $label, 'date' => $r['date_facture'], 'type' => 'facture'];
    }
}

if ($hasRdv) {
    $joinUser = $hasUser ? "LEFT JOIN user um ON um.id_user = rv.id_medecin" : "";
    $selectUser = $hasUser ? ", um.Nom AS mnom, um.Prenom AS mprenom" : ", '' AS mnom, '' AS mprenom";
    $rdvs = safeRows($pdo,
        "SELECT rv.date_rdv, rv.date_demande, rv.type_consultation $selectUser
         FROM rendez_vous rv $joinUser
         ORDER BY rv.date_demande DESC, rv.date_rdv DESC LIMIT 5", [], 'activites_rdv');
    foreach ($rdvs as $r) {
        $dr = trim(($r['mprenom'] ?? '') . ' ' . ($r['mnom'] ?? '')) ?: 'médecin inconnu';
        $activites[] = [
            'label' => "Nouveau RDV — Dr. $dr — {$r['date_rdv']} ({$r['type_consultation']})",
            'date'  => $r['date_demande'] ?: $r['date_rdv'],
            'type'  => 'rdv'
        ];
    }
}

if ($hasAdmission) {
    $joinSalle = ($hasSalle && columnExists($pdo, 'salle', 'numero')) ? "LEFT JOIN salle s ON s.id_salle = a.id_salle" : "";
    $selectSalle = ($hasSalle && columnExists($pdo, 'salle', 'numero')) ? ", s.numero AS salle_num" : ", NULL AS salle_num";
    $adms = safeRows($pdo,
        "SELECT a.id_admission, a.date_arrive_relle, a.mode_entree $selectSalle
         FROM admission a $joinSalle
         ORDER BY a.date_arrive_relle DESC LIMIT 5", [], 'activites_admissions');
    foreach ($adms as $r) {
        $salle = !empty($r['salle_num']) ? "Salle {$r['salle_num']}" : 'Salle inconnue';
        $activites[] = [
            'label' => "Admission #{$r['id_admission']} — $salle — {$r['mode_entree']}",
            'date'  => $r['date_arrive_relle'],
            'type'  => 'admission'
        ];
    }
}

if ($hasPanne) {
    $joinRes = $hasRessource ? "LEFT JOIN ressources r ON r.id_ressource = p.id_ressource" : "";
    $selectRes = $hasRessource ? ", r.Nom AS res_nom" : ", '' AS res_nom";
    $pannes = safeRows($pdo,
        "SELECT p.date_de_panne, p.description $selectRes
         FROM panne p $joinRes
         ORDER BY p.date_de_panne DESC LIMIT 3", [], 'activites_pannes');
    foreach ($pannes as $r) {
        $res = $r['res_nom'] ?: 'Ressource';
        $activites[] = [
            'label' => "Panne signalée — {$res} : {$r['description']}",
            'date'  => $r['date_de_panne'],
            'type'  => 'panne'
        ];
    }
}

usort($activites, fn($a, $b) => strcmp((string)$b['date'], (string)$a['date']));
$activites = array_slice($activites, 0, 8);

echo json_encode([
    'success' => true,
    'kpi' => [
        'factures_mois'          => $factures_mois,
        'factures_attente'       => $factures_attente,
        'rdv_aujourd_hui'        => $rdv_aujourd_hui,
        'rdv_avenir'             => $rdv_avenir,
        'rdv_confirmes'          => $rdv_confirmes,
        'rdv_attente'            => $rdv_attente,
        'rdv_refuses'            => $rdv_refuses,
        'admissions_total'       => $admissions_total,
        'admissions_aujourd_hui' => $admissions_aujourd_hui,
        'ressources_en_service'  => $ressources_en_service,
        'ressources_en_panne'    => $ressources_en_panne,
    ],
    'modules' => [
        'factures'   => $mod_factures,
        'rdv'        => $mod_rdv,
        'admissions' => $mod_admissions,
        'dossiers'   => $mod_dossiers,
        'ressources' => $mod_ressources,
        'medecins'   => $mod_medecins,
    ],
    'activites' => $activites,
    'debug' => $debugErrors
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
