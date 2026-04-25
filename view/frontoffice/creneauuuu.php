<?php
require '../../config.php';
header('Content-Type: application/json; charset=utf-8');

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$db     = config::getConnexion();

/* ─────────────────────────────────────────────────────────
   FORMATTERS
   ───────────────────────────────────────────────────────── */
function formatCreneauForFront(array $row): array
{
    $heureDebut = substr($row['heure_debut'], 0, 5);
    $heureFin   = substr($row['heure_fin'], 0, 5);

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
        'service'     => $row['Service'] ?? ''
    ];
}

/* ─────────────────────────────────────────────────────────
   ACTIONS
   ───────────────────────────────────────────────────────── */

if ($action === 'medecins') {
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

} elseif ($action === 'charger') {
    try {
        $sql = "SELECT c.id_creneau,
                       c.date_creneau AS date,
                       c.heure_debut,
                       c.heure_fin,
                       c.statut,
                       c.id_medecin,
                       CONCAT(COALESCE(u.Prenom,''), ' ', COALESCE(u.Nom,'')) AS nom_medecin,
                       u.Service
                FROM creneau c
                INNER JOIN user u ON u.id_user = c.id_medecin
                WHERE LOWER(c.statut) = 'disponible'
                  AND u.id_role = 'ROLE-MED'
                  AND c.date_creneau >= CURDATE()
                ORDER BY c.date_creneau ASC, c.heure_debut ASC";

        $query = $db->prepare($sql);
        $query->execute();
        $rows = $query->fetchAll(PDO::FETCH_ASSOC);

        $data = array_map('formatCreneauForFront', $rows);

        echo json_encode([
            "success" => true,
            "data"    => $data
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => $e->getMessage()
        ]);
    }

} elseif ($action === 'charger_medecin') {
    $id_medecin = trim($_GET['medecin'] ?? '');

    if ($id_medecin === '') {
        echo json_encode(["success" => false, "message" => "ID médecin manquant"]);
        exit;
    }

    try {
        $sql = "SELECT c.id_creneau,
                       c.date_creneau AS date,
                       c.heure_debut,
                       c.heure_fin,
                       c.statut,
                       c.id_medecin,
                       CONCAT(COALESCE(u.Prenom,''), ' ', COALESCE(u.Nom,'')) AS nom_medecin,
                       u.Service
                FROM creneau c
                LEFT JOIN user u ON u.id_user = c.id_medecin
                WHERE c.id_medecin = :medecin
                ORDER BY c.date_creneau ASC, c.heure_debut ASC";

        $query = $db->prepare($sql);
        $query->execute([':medecin' => $id_medecin]);
        $rows = $query->fetchAll(PDO::FETCH_ASSOC);

        $data = array_map('formatCreneauForFront', $rows);

        echo json_encode([
            "success"  => true,
            "creneaux" => $data
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => $e->getMessage()
        ]);
    }

} elseif ($action === 'charger_date') {
    $date = trim($_GET['date'] ?? '');

    if ($date === '') {
        echo json_encode(["success" => false, "message" => "Date manquante"]);
        exit;
    }

    if ($date < date('Y-m-d')) {
        echo json_encode(["success" => false, "message" => "date_invalide"]);
        exit;
    }

    try {
        $sql = "SELECT c.id_creneau,
                       c.date_creneau AS date,
                       c.heure_debut,
                       c.heure_fin,
                       c.statut,
                       c.id_medecin,
                       CONCAT(COALESCE(u.Prenom,''), ' ', COALESCE(u.Nom,'')) AS nom_medecin,
                       u.Service
                FROM creneau c
                INNER JOIN user u ON u.id_user = c.id_medecin
                WHERE c.date_creneau = :date
                ORDER BY c.heure_debut ASC";

        $query = $db->prepare($sql);
        $query->execute([':date' => $date]);
        $rows = $query->fetchAll(PDO::FETCH_ASSOC);

        $data = array_map('formatCreneauForFront', $rows);

        echo json_encode([
            "success"  => true,
            "creneaux" => $data
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => $e->getMessage()
        ]);
    }

} elseif ($action === 'charger_filtre') {
    $id_medecin = trim($_GET['medecin'] ?? '');
    $date       = trim($_GET['date'] ?? '');

    if ($id_medecin === '' && $date === '') {
        echo json_encode([
            "success" => false,
            "message" => "Filtre manquant (médecin ou date)"
        ]);
        exit;
    }

    try {
        $conditions = ["1=1"];
        $params     = [];

        if ($id_medecin !== '') {
            $conditions[]       = "c.id_medecin = :medecin";
            $params[':medecin'] = $id_medecin;
        }

        if ($date !== '') {
            $conditions[]    = "c.date_creneau = :date";
            $params[':date'] = $date;
        }

        $where = implode(' AND ', $conditions);

        $sql = "SELECT c.id_creneau,
                       c.date_creneau AS date,
                       c.heure_debut,
                       c.heure_fin,
                       c.statut,
                       c.id_medecin,
                       CONCAT(COALESCE(u.Prenom,''), ' ', COALESCE(u.Nom,'')) AS nom_medecin,
                       u.Service
                FROM creneau c
                INNER JOIN user u ON u.id_user = c.id_medecin
                WHERE $where
                ORDER BY c.date_creneau ASC, c.heure_debut ASC";

        $query = $db->prepare($sql);
        $query->execute($params);
        $rows = $query->fetchAll(PDO::FETCH_ASSOC);

        $data = array_map('formatCreneauForFront', $rows);

        echo json_encode([
            "success"  => true,
            "creneaux" => $data
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => $e->getMessage()
        ]);
    }

} elseif ($action === 'detail') {
    $id_creneau = trim($_GET['creneau'] ?? '');

    if ($id_creneau === '') {
        echo json_encode(["success" => false, "message" => "ID créneau manquant"]);
        exit;
    }

    try {
        $sql = "SELECT c.id_creneau,
                       c.date_creneau AS date,
                       c.heure_debut,
                       c.heure_fin,
                       c.statut,
                       c.id_medecin,
                       CONCAT(COALESCE(u.Prenom,''), ' ', COALESCE(u.Nom,'')) AS nom_medecin,
                       u.Service
                FROM creneau c
                LEFT JOIN user u ON u.id_user = c.id_medecin
                WHERE c.id_creneau = :creneau";

        $query = $db->prepare($sql);
        $query->execute([':creneau' => $id_creneau]);
        $row = $query->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            echo json_encode(["success" => false, "message" => "creneau_introuvable"]);
            exit;
        }

        echo json_encode([
            "success" => true,
            "data"    => formatCreneauForFront($row)
        ]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            "success" => false,
            "message" => $e->getMessage()
        ]);
    }

} elseif ($action === 'stats') {
    try {
        $q = $db->prepare("SELECT COUNT(*) AS disponibles
                           FROM creneau
                           WHERE statut = 'disponible'
                             AND date_creneau >= CURDATE()");
        $q->execute();

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
