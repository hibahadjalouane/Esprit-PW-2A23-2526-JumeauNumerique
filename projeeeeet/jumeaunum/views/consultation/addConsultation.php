<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Consultation.php';
require_once __DIR__ . '/../../controllers/ConsultationController.php';

$controller = new ConsultationController($conn);

$id_dossier = $_GET['id'];

$message = "";

if (isset($_POST['submit'])) {

    $data = [
        "date_consultation" => $_POST['date_consultation'],
        "motif" => $_POST['motif'],
        "diagnostic" => $_POST['diagnostic'],
        "notes" => $_POST['notes'],
        "id_dossier" => $id_dossier
    ];

    $result = $controller->add($data);

    $message = $result ? "✅ Consultation added successfully" : "❌ Error adding consultation";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Consultation</title>

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
            <h1>Add Consultation</h1>
        </div>

        <div class="table-container" style="max-width:600px; margin:auto;">

            <p style="color:green;">
                <?= $message ?>
            </p>

            <form method="POST">

                <label>Date</label><br>
                <input type="date" name="date_consultation" required><br><br>

                <label>Motif</label><br>
                <input type="text" name="motif" required><br><br>

                <label>Diagnostic</label><br>
                <input type="text" name="diagnostic" required><br><br>

                <label>Notes</label><br>
                <input type="text" name="notes"><br><br>

                <button type="submit" name="submit" class="btn-add">
                    Add Consultation
                </button>

            </form>

        </div>

    </div>

</div>

</body>
</html>