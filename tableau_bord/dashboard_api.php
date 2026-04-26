<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

require_once '../gestion_paiement/modele/config.php';
$pdo = config::getConnexion();

function scalar($pdo, $sql, $params = []) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch(PDO::FETCH_NUM);
    return $row ? (int)$row[0] : 0;
}

function rows($pdo, $sql, $params = []) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

$today    = date('Y-m-d');
$tomorrow = date('Y-m-d', strtotime('+1 day'));
$month    = date('Y-m');

// ── KPI ──────────────────────────────────────────────────────────────────────
$factures_mois    = scalar($pdo,
    "SELECT COUNT(*) FROM facture WHERE DATE_FORMAT(date_facture,'%Y-%m') = ?", [$month]);
$factures_attente = scalar($pdo,
    "SELECT COUNT(*) FROM facture WHERE statut = 'Non payee'");

$rdv_aujourd_hui  = scalar($pdo,
    "SELECT COUNT(*) FROM rendez_vous WHERE date_rdv = ?", [$today]);
$rdv_avenir       = scalar($pdo,
    "SELECT COUNT(*) FROM rendez_vous WHERE date_rdv >= ?", [$tomorrow]);

$admissions_total       = scalar($pdo, "SELECT COUNT(*) FROM admission");
$admissions_aujourd_hui = scalar($pdo,
    "SELECT COUNT(*) FROM admission WHERE date_arrive_relle = ?", [$today]);

$ressources_en_service = scalar($pdo,
    "SELECT COUNT(*) FROM ressources WHERE Statut = 'en service'");
$ressources_en_panne   = scalar($pdo,
    "SELECT COUNT(*) FROM ressources WHERE Statut = 'en panne'");

// ── MODULES ───────────────────────────────────────────────────────────────────
$mod_factures   = scalar($pdo, "SELECT COUNT(*) FROM facture WHERE statut = 'Non payee'");
$mod_rdv        = scalar($pdo, "SELECT COUNT(*) FROM rendez_vous WHERE date_rdv = ?", [$today]);
$mod_admissions = scalar($pdo, "SELECT COUNT(*) FROM admission");
$mod_dossiers   = scalar($pdo, "SELECT COUNT(*) FROM dossier_medical");
$mod_ressources = scalar($pdo, "SELECT COUNT(*) FROM ressources WHERE Statut = 'en service'");
$mod_medecins   = scalar($pdo, "SELECT COUNT(*) FROM user WHERE id_role = 3");

// ── ACTIVITES RECENTES ────────────────────────────────────────────────────────
$activites = [];

// Factures
$facs = rows($pdo,
    "SELECT f.id_facture, f.date_facture, f.montant, f.statut,
            u.Nom, u.Prenom
     FROM facture f
     LEFT JOIN user u ON u.id_user = f.id_patient
     ORDER BY f.date_facture DESC LIMIT 5");
foreach ($facs as $r) {
    $patient = trim(($r['Prenom'] ?? '') . ' ' . ($r['Nom'] ?? ''));
    if ($r['statut'] === 'payee') {
        $label = "Paiement recu — Facture #{$r['id_facture']} — {$r['montant']} TND";
    } elseif ($r['statut'] === 'en retard') {
        $label = "Facture #{$r['id_facture']} en retard — Patient : $patient";
    } else {
        $label = "Facture #{$r['id_facture']} creee — Patient : $patient — {$r['montant']} TND";
    }
    $activites[] = ['label' => $label, 'date' => $r['date_facture'], 'type' => 'facture'];
}

// Rendez-vous
$rdvs = rows($pdo,
    "SELECT rv.date_rdv, rv.date_demande, rv.type_consultation,
            um.Nom AS mnom, um.Prenom AS mprenom
     FROM rendez_vous rv
     LEFT JOIN user um ON um.id_user = rv.id_medecin
     ORDER BY rv.date_demande DESC LIMIT 5");
foreach ($rdvs as $r) {
    $dr = trim(($r['mprenom'] ?? '') . ' ' . ($r['mnom'] ?? ''));
    $activites[] = [
        'label' => "Nouveau RDV — Dr. $dr — {$r['date_rdv']} ({$r['type_consultation']})",
        'date'  => $r['date_demande'] ?? $r['date_rdv'],
        'type'  => 'rdv'
    ];
}

// Admissions
$adms = rows($pdo,
    "SELECT a.id_admission, a.date_arrive_relle, a.mode_entree, s.numero AS salle_num
     FROM admission a
     LEFT JOIN salle s ON s.id_salle = a.id_salle
     ORDER BY a.date_arrive_relle DESC LIMIT 5");
foreach ($adms as $r) {
    $salle = isset($r['salle_num']) ? "Salle {$r['salle_num']}" : 'Salle inconnue';
    $activites[] = [
        'label' => "Admission #{$r['id_admission']} — $salle — {$r['mode_entree']}",
        'date'  => $r['date_arrive_relle'],
        'type'  => 'admission'
    ];
}

// Pannes
$pannes = rows($pdo,
    "SELECT p.date_de_panne, p.description, r.Nom AS res_nom
     FROM panne p
     LEFT JOIN ressources r ON r.id_ressource = p.id_ressource
     ORDER BY p.date_de_panne DESC LIMIT 3");
foreach ($pannes as $r) {
    $activites[] = [
        'label' => "Panne signalee — {$r['res_nom']} : {$r['description']}",
        'date'  => $r['date_de_panne'],
        'type'  => 'panne'
    ];
}

// Trier par date desc, garder les 8 plus recentes
usort($activites, fn($a, $b) => strcmp($b['date'], $a['date']));
$activites = array_slice($activites, 0, 8);

// ── REPONSE ───────────────────────────────────────────────────────────────────
echo json_encode([
    'kpi' => [
        'factures_mois'          => $factures_mois,
        'factures_attente'       => $factures_attente,
        'rdv_aujourd_hui'        => $rdv_aujourd_hui,
        'rdv_avenir'             => $rdv_avenir,
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
], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);