<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/DossierMedical.php';
require_once __DIR__ . '/../../controllers/DossierMedicalController.php';

$controller = new DossierMedicalController($conn);

$stmt = $conn->prepare("SELECT id_user, Nom FROM user");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$message = "";
$error = "";

if (isset($_POST['submit'])) {

    $id_dossier = trim($_POST['id_dossier']);
    $description = trim($_POST['description']);
    $date_creation = $_POST['date_creation'];
    $id_patient = $_POST['id_patient'];
    $id_medecin = $_POST['id_medecin'];

    // 🔥 CONTROLE DE SAISIE

    if (
        empty($id_dossier) ||
        empty($description) ||
        empty($date_creation) ||
        empty($id_patient) ||
        empty($id_medecin)
    ) {
        $error = " All fields are required.";
    }

    elseif (!preg_match("/^D[0-9]+$/", $id_dossier)) {
        $error = " ID must be like D001, D123...";
    }

    elseif (strlen($description) < 5) {
        $error = " Description must be at least 5 characters.";
    }

    elseif ($date_creation > date("Y-m-d")) {
        $error = " Date cannot be in the future.";
    }

    elseif ($id_patient == $id_medecin) {
        $error = " Patient and Medecin must be different.";
    }

    else {

        $data = [
            "id_dossier" => $id_dossier,
            "description" => $description,
            "date_creation" => $date_creation,
            "id_patient" => $id_patient,
            "id_medecin" => $id_medecin
        ];

        $result = $controller->add($data);

        if ($result) {
            $message = "✅ Dossier added successfully";
        } else {
            $error = "❌ Error inserting dossier";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Dossier</title>

    <link rel="stylesheet" href="/projeeeeet/jumeaunum/public/css/style.css">
</head>

<body>

<div class="container">

    <!-- SIDEBAR -->
    <div class="sidebar">
    <div class="logo">
        <img src="/projeeeeet/jumeaunum/public/img/logo.png" alt="logo">
        <h2>JumeauNum</h2>
    </div>

    <a href="/projeeeeet/jumeaunum/index.php">🏠 Accueil</a>
    <a href="../dossier/dossierList.php">📁 Dossiers Médicaux</a>
    <a href="../consultation/list.php">🩺 Consultations</a>
</div>

    <!-- MAIN -->
    <div class="main">

        <div class="topbar">
            <h1>Add Medical Dossier</h1>
        </div>

        <div class="table-container">

            <?php if (!empty($error)) : ?>
    <p style="color:red; font-weight:bold;">
        <?= $error ?>
    </p>
<?php endif; ?>

<?php if (!empty($message)) : ?>
    <p style="color:green; font-weight:bold;">
        <?= $message ?>
    </p>
<?php endif; ?>

            <form method="POST">

                <label>ID Dossier</label><br>
                <input type="text" name="id_dossier" required><br><br>

                <label>Description</label><br>
                <input type="text" name="description" required><br><br>

                <label>Date Creation</label><br>
                <input type="date" name="date_creation" required><br><br>

                <label>Patient</label><br>
                <select name="id_patient" required>
                    <option value="">-- Select Patient --</option>
                    <?php foreach ($users as $u) { ?>
                        <option value="<?= $u['id_user'] ?>">
                            <?= $u['id_user'] ?> - <?= $u['Nom'] ?>
                        </option>
                    <?php } ?>
                </select>

                <br><br>

                <label>Medecin</label><br>
                <select name="id_medecin" required>
                    <option value="">-- Select Medecin --</option>
                    <?php foreach ($users as $u) { ?>
                        <option value="<?= $u['id_user'] ?>">
                            <?= $u['id_user'] ?> - <?= $u['Nom'] ?>
                        </option>
                    <?php } ?>
                </select>

                <br><br>

                <button type="submit" name="submit" class="btn-add">
                    Add Dossier
                </button>

            </form>

        </div>

    </div>

</div>

</body>
</html>