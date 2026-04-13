<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/DossierMedical.php';
require_once __DIR__ . '/../../controllers/DossierMedicalController.php';

$controller = new DossierMedicalController($conn);

$id = $_GET['id'];

$sql = "SELECT * FROM dossier_medical WHERE id_dossier = ?";
$stmt = $conn->prepare($sql);
$stmt->execute([$id]);
$dossier = $stmt->fetch(PDO::FETCH_ASSOC);

$stmt = $conn->prepare("SELECT id_user, Nom FROM user");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$message = "";
$errors = [];

if (isset($_POST['update'])) {

    // 🔥 CONTROLE DE SAISIE
    if (empty($_POST['description'])) {
    $errors['description'] = "Description is required";
}
elseif (strlen($_POST['description']) > 255) {
    $errors['description'] = "Description must not exceed 255 characters";
}

   if (empty($_POST['date_creation'])) {
    $errors['date_creation'] = "Date is required";
}
elseif ($_POST['date_creation'] > date('Y-m-d')) {
    $errors['date_creation'] = "Date cannot be in the future";
}

    if (empty($_POST['id_patient'])) {
    $errors['id_patient'] = "Patient is required";
}

   if (empty($_POST['id_medecin'])) {
    $errors['id_medecin'] = "Medecin is required";
}
if (!empty($_POST['id_patient']) &&
    !empty($_POST['id_medecin']) &&
    $_POST['id_patient'] === $_POST['id_medecin']) {

    $errors['id_medecin'] = "Patient and Medecin cannot be the same person";
}
    // ✅ if no errors → update
    if (empty($errors)) {

        $data = [
            "id_dossier" => $_POST['id_dossier'],
            "description" => $_POST['description'],
            "date_creation" => $_POST['date_creation'],
            "id_patient" => $_POST['id_patient'],
            "id_medecin" => $_POST['id_medecin']
        ];

        $result = $controller->update($data);

        $message = $result ? "✅ Dossier updated successfully" : "❌ Update failed";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Dossier</title>

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

        <div class="form-card">

            <h2>✏ Edit Dossier Médical</h2>

            <?php if (!empty($message)) { ?>
    <p style="text-align:center; color:green;">
        <?= $message ?>
    </p>
<?php } ?>

            <form method="POST">

                <input type="hidden" name="id_dossier" value="<?= $dossier['id_dossier'] ?>">

                <label>Description</label>
                <input type="text" name="description"
       value="<?= $dossier['description'] ?>">
       
<span style="color:red;">
    <?= $errors['description'] ?? '' ?>
</span>

                <label>Date Creation</label>
               <input type="date" name="date_creation"
       value="<?= $dossier['date_creation'] ?>">

<span style="color:red;">
    <?= $errors['date_creation'] ?? '' ?>
</span>

                <label>Patient</label>
                <select name="id_patient" required>
                    <?php foreach ($users as $u) { ?>
                        <option value="<?= $u['id_user'] ?>"
                            <?= $u['id_user'] == $dossier['id_patient'] ? 'selected' : '' ?>>
                            <?= $u['id_user'] ?> - <?= $u['Nom'] ?>
                        </option>
                    <?php } ?>
                </select>
                <span style="color:red;">
    <?= $errors['id_patient'] ?? '' ?>
</span>

                <label>Medecin</label>
                <select name="id_medecin" required>
                    <?php foreach ($users as $u) { ?>
                        <option value="<?= $u['id_user'] ?>"
                            <?= $u['id_user'] == $dossier['id_medecin'] ? 'selected' : '' ?>>
                            <?= $u['id_user'] ?> - <?= $u['Nom'] ?>
                        </option>
                    <?php } ?>
                </select>
                <span style="color:red;">
    <?= $errors['id_medecin'] ?? '' ?>
</span>

                <button type="submit" name="update" class="btn-save">
                    💾 Update Dossier
                </button>

            </form>

        </div>

    </div>

</div>

</body>
</html>