<?php

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../controllers/ConsultationController.php';

$consultationController = new ConsultationController($conn);

$id = $_GET['id'];

// DOSSIER INFO
$sql = "
SELECT 
    d.*,
    p.Nom AS patient_name,
    m.Nom AS medecin_name
FROM dossier_medical d
JOIN user p ON d.id_patient = p.id_user
JOIN user m ON d.id_medecin = m.id_user
WHERE d.id_dossier = ?
";

$stmt = $conn->prepare($sql);
$stmt->execute([$id]);
$dossier = $stmt->fetch(PDO::FETCH_ASSOC);

// CONSULTATIONS
$consultations = $consultationController->getByDossier($id);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dossier Details</title>

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
            <h1>Dossier Details</h1>
        </div>

        <!-- DOSSIER CARD -->
        <div class="table-container">

            <h3>📁 Medical File</h3>

            <p><b>ID:</b> <?= $dossier['id_dossier'] ?></p>
            <p><b>Description:</b> <?= $dossier['description'] ?></p>
            <p><b>Date:</b> <?= $dossier['date_creation'] ?></p>
            <p><b>Patient:</b> <?= $dossier['patient_name'] ?></p>
            <p><b>Medecin:</b> <?= $dossier['medecin_name'] ?></p>

        </div>

        <br>

        <!-- CONSULTATIONS -->
        <div class="table-container">

            <h3>🩺 Consultations</h3>

            <a href="../consultation/addConsultation.php?id=<?= $id ?>" class="btn-add">
                + Add Consultation
            </a>

            <br><br>

            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Motif</th>
                        <th>Diagnostic</th>
                        <th>Notes</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                <?php foreach ($consultations->fetchAll(PDO::FETCH_ASSOC) as $c) { ?>

                    <tr>
                        <td><?= $c['date_consultation'] ?></td>
                        <td><?= $c['motif'] ?></td>
                        <td><?= $c['diagnostic'] ?></td>
                        <td><?= $c['notes'] ?></td>

                        <td>
                            <a href="../consultation/editConsultation.php?id=<?= $c['id_consultation'] ?>" class="btn-edit">
                                ✏
                            </a>

                            <a href="../consultation/deleteConsultation.php?id=<?= $c['id_consultation'] ?>"
                               class="btn-delete"
                               onclick="return confirm('Delete this consultation?')">
                                ❌
                            </a>
                        </td>
                    </tr>

                <?php } ?>

                </tbody>
            </table>

        </div>

    </div>

</div>

</body>
</html>