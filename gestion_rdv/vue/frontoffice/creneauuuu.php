<?php
require_once dirname(__DIR__, 3) . '/config.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$db     = config::getConnexion();

/* ── Formatage d'un créneau pour le frontoffice ──────────── */
function formatCreneauForFront(array $row): array
{
    $heureDebut = substr($row['heure_debut'], 0, 5);
    $heureFin   = substr($row['heure_fin'],   0, 5);
    return [
        'id'          => $row['id_creneau'],
        'date'        => $row['date'],
        'heure_debut' => $row['heure_debut'],
        'heure_fin'   => $row['heure_fin'],
        'time'        => $heureDebut . ' - ' . $heureFin,
        'statut'      => $row['statut'],
        'available'   => strtolower($row['statut']) === 'disponible',
        'id_medecin'  => $row['id_medecin'],
        'doctor'      => trim($row['nom_medecin'] ?? ''),
        'nom_medecin' => trim($row['nom_medecin'] ?? ''),
        'type'        => $row['Service'] ?? '',
        'service'     => $row['Service'] ?? '',
    ];
}

/* ── Liste des médecins (id_role = 3) ────────────────────── */
if ($action === 'medecins') {
    try {
        $sql = "SELECT id_user,
                       CONCAT(COALESCE(Prenom,''), ' ', COALESCE(Nom,'')) AS nom_complet,
                       Service
                FROM user
                WHERE id_role = 3
                ORDER BY Prenom, Nom";
        $q = $db->prepare($sql);
        $q->execute();
        echo json_encode(['success' => true, 'data' => $q->fetchAll(PDO::FETCH_ASSOC)]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

/* ── Créneaux disponibles (tous les médecins) ────────────── */
if ($action === 'charger') {
    try {
        $sql = "SELECT c.id_creneau,
                       c.date_creneau AS date,
                       c.heure_debut, c.heure_fin, c.statut, c.id_medecin,
                       CONCAT(COALESCE(u.Prenom,''), ' ', COALESCE(u.Nom,'')) AS nom_medecin,
                       u.Service
                FROM creneau c
                INNER JOIN user u ON u.id_user = c.id_medecin
                WHERE LOWER(c.statut) = 'disponible'
                  AND u.id_role = 3
                  AND c.date_creneau >= CURDATE()
                ORDER BY c.date_creneau ASC, c.heure_debut ASC";
        $q = $db->prepare($sql);
        $q->execute();
        $data = array_map('formatCreneauForFront', $q->fetchAll(PDO::FETCH_ASSOC));
        echo json_encode(['success' => true, 'data' => $data]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

/* ── Créneaux d'un médecin précis ────────────────────────── */
if ($action === 'charger_medecin') {
    $id_medecin = trim($_GET['medecin'] ?? '');
    if ($id_medecin === '' || !ctype_digit($id_medecin)) {
        echo json_encode(['success' => false, 'message' => 'ID médecin manquant ou invalide']);
        exit;
    }
    try {
        $sql = "SELECT c.id_creneau,
                       c.date_creneau AS date,
                       c.heure_debut, c.heure_fin, c.statut, c.id_medecin,
                       CONCAT(COALESCE(u.Prenom,''), ' ', COALESCE(u.Nom,'')) AS nom_medecin,
                       u.Service
                FROM creneau c
                LEFT JOIN user u ON u.id_user = c.id_medecin
                WHERE c.id_medecin = :medecin
                ORDER BY c.date_creneau ASC, c.heure_debut ASC";
        $q = $db->prepare($sql);
        $q->execute([':medecin' => (int)$id_medecin]);
        $data = array_map('formatCreneauForFront', $q->fetchAll(PDO::FETCH_ASSOC));
        echo json_encode(['success' => true, 'creneaux' => $data]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

/* ── Créneaux d'une date précise ─────────────────────────── */
if ($action === 'charger_date') {
    $date = trim($_GET['date'] ?? '');
    if ($date === '')                 { echo json_encode(['success' => false, 'message' => 'Date manquante']); exit; }
    if ($date < date('Y-m-d'))        { echo json_encode(['success' => false, 'message' => 'date_invalide']);  exit; }
    try {
        $sql = "SELECT c.id_creneau,
                       c.date_creneau AS date,
                       c.heure_debut, c.heure_fin, c.statut, c.id_medecin,
                       CONCAT(COALESCE(u.Prenom,''), ' ', COALESCE(u.Nom,'')) AS nom_medecin,
                       u.Service
                FROM creneau c
                INNER JOIN user u ON u.id_user = c.id_medecin
                WHERE c.date_creneau = :date
                ORDER BY c.heure_debut ASC";
        $q = $db->prepare($sql);
        $q->execute([':date' => $date]);
        $data = array_map('formatCreneauForFront', $q->fetchAll(PDO::FETCH_ASSOC));
        echo json_encode(['success' => true, 'creneaux' => $data]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

/* ── Filtre combiné médecin + date ───────────────────────── */
if ($action === 'charger_filtre') {
    $id_medecin = trim($_GET['medecin'] ?? '');
    $date       = trim($_GET['date']    ?? '');
    if ($id_medecin === '' && $date === '') {
        echo json_encode(['success' => false, 'message' => 'Filtre manquant']);
        exit;
    }
    try {
        $conditions = ['1=1'];
        $params     = [];
        if ($id_medecin !== '' && ctype_digit($id_medecin)) {
            $conditions[]       = 'c.id_medecin = :medecin';
            $params[':medecin'] = (int)$id_medecin;
        }
        if ($date !== '') {
            $conditions[]    = 'c.date_creneau = :date';
            $params[':date'] = $date;
        }
        $where = implode(' AND ', $conditions);
        $sql = "SELECT c.id_creneau,
                       c.date_creneau AS date,
                       c.heure_debut, c.heure_fin, c.statut, c.id_medecin,
                       CONCAT(COALESCE(u.Prenom,''), ' ', COALESCE(u.Nom,'')) AS nom_medecin,
                       u.Service
                FROM creneau c
                INNER JOIN user u ON u.id_user = c.id_medecin
                WHERE $where
                ORDER BY c.date_creneau ASC, c.heure_debut ASC";
        $q = $db->prepare($sql);
        $q->execute($params);
        $data = array_map('formatCreneauForFront', $q->fetchAll(PDO::FETCH_ASSOC));
        echo json_encode(['success' => true, 'creneaux' => $data]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

/* ── Détail d'un créneau ─────────────────────────────────── */
if ($action === 'detail') {
    $id_creneau = trim($_GET['creneau'] ?? '');
    if ($id_creneau === '' || !ctype_digit($id_creneau)) {
        echo json_encode(['success' => false, 'message' => 'ID créneau manquant ou invalide']);
        exit;
    }
    try {
        $sql = "SELECT c.id_creneau,
                       c.date_creneau AS date,
                       c.heure_debut, c.heure_fin, c.statut, c.id_medecin,
                       CONCAT(COALESCE(u.Prenom,''), ' ', COALESCE(u.Nom,'')) AS nom_medecin,
                       u.Service
                FROM creneau c
                LEFT JOIN user u ON u.id_user = c.id_medecin
                WHERE c.id_creneau = :creneau";
        $q = $db->prepare($sql);
        $q->execute([':creneau' => (int)$id_creneau]);
        $row = $q->fetch(PDO::FETCH_ASSOC);
        if (!$row) { echo json_encode(['success' => false, 'message' => 'creneau_introuvable']); exit; }
        echo json_encode(['success' => true, 'data' => formatCreneauForFront($row)]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

/* ── Statistiques créneaux ───────────────────────────────── */
if ($action === 'stats') {
    try {
        $q = $db->prepare("SELECT COUNT(*) AS disponibles
                           FROM creneau
                           WHERE statut = 'disponible'
                             AND date_creneau >= CURDATE()");
        $q->execute();
        echo json_encode(['success' => true, 'data' => $q->fetch(PDO::FETCH_ASSOC)]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Action inconnue']);
?>