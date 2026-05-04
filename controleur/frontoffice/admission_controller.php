<?php
// Controller: Frontoffice - Admission List
// Architecture: MVC | Module: gestion_admission

// Load model config (DB connection)
require_once __DIR__ . '/../../modele/config.php';
// Load Admission and Salle model classes
require_once __DIR__ . '/../../modele/Admission.php';
require_once __DIR__ . '/../../modele/Salle.php';

// ─── Fetch all admissions with room info via JOIN ──────────────────────────
$sql = "
    SELECT
        a.id_admission,
        a.date_arrive_relle,
        a.mode_entree,
        a.id_ticket,
        a.id_patient,
        s.id_salle,
        s.numero   AS salle_numero,
        s.statut   AS salle_statut
    FROM admission a
    INNER JOIN salle s ON a.id_salle = s.id_salle
    ORDER BY a.date_arrive_relle DESC
";

$admissions = [];

try {
    // $pdo is expected to be defined in modele/config.php
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $admissions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Pass error message to view for display
    $db_error = "Erreur base de données : " . htmlspecialchars($e->getMessage());
}

// ─── Separate active vs completed admissions ──────────────────────────────
// "Active" = the salle statut is 'occupée' (or first result as fallback)
$active_admission = null;
$history_admissions = [];

foreach ($admissions as $adm) {
    if ($active_admission === null && strtolower($adm['salle_statut']) === 'occupée') {
        $active_admission = $adm;
    } else {
        $history_admissions[] = $adm;
    }
}

// If no "occupée" room found, treat the most recent as active
if ($active_admission === null && !empty($admissions)) {
    $active_admission = array_shift($admissions);
    $history_admissions = $admissions;
}

// ─── Fetch notifications for the current patient ──────────────────────────
// id_patient vient de la session ou du premier enregistrement trouvé
$id_patient_courant = $_SESSION['id_user'] ?? $_GET['id_patient'] ?? null;

// Fallback : si pas de session, on prend l'id_patient de la première admission
if (!$id_patient_courant && !empty($admissions)) {
    foreach ($admissions as $a) {
        if (!empty($a['id_patient'])) {
            $id_patient_courant = $a['id_patient'];
            break;
        }
    }
}

$notifications     = [];
$nb_notifs_non_lues = 0;

if ($id_patient_courant) {
    try {
        $nStmt = $pdo->prepare("
            SELECT id_notif, type, titre, message, id_reference, date_creation, lu
            FROM notifications
            WHERE id_user = :id_user
            ORDER BY date_creation DESC
            LIMIT 30
        ");
        $nStmt->execute(['id_user' => $id_patient_courant]);
        $notifications      = $nStmt->fetchAll(PDO::FETCH_ASSOC);
        $nb_notifs_non_lues = count(array_filter($notifications, fn($n) => !$n['lu']));
    } catch (PDOException $ignored) {
        // Table notifications pas encore créée — on ignore silencieusement
    }
}

// ─── Load the view ────────────────────────────────────────────────────────
require_once __DIR__ . '/../../vue/frontoffice/admission_list.php';