<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/DossierMedical.php';

$dossier = new DossierMedical($conn);
$stmt = $dossier->getAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dossier Médical</title>

    <link rel="stylesheet" href="/projeeeeet/jumeaunum/public/css/style.css">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>

<div class="container">

    <!-- SIDEBAR (optional if you use it globally) -->
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
            <h1>📁 Dossiers Médicaux</h1>

            <a href="addDossier.php" class="btn-add">
                + Nouveau Dossier
            </a>
        </div>

        <div class="table-container">

            <table>

                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Description</th>
                        <th>Date</th>
                        <th>Patient</th>
                        <th>Médecin</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>

                <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) { ?>

                    <tr>
                        <td><?= $row['id_dossier'] ?></td>
                        <td><?= $row['description'] ?></td>
                        <td><?= $row['date_creation'] ?></td>
                        <td><?= $row['patient_name'] ?></td>
                        <td><?= $row['medecin_name'] ?></td>

                        <td class="actions">

                            <a href="dossierDetails.php?id=<?= $row['id_dossier'] ?>" class="btn-view">
                                <i class="fa fa-eye"></i>
                            </a>

                            <a href="editDossier.php?id=<?= $row['id_dossier'] ?>" class="btn-edit">
                                <i class="fa fa-pen"></i>
                            </a>

                            <a href="deleteDossier.php?id=<?= $row['id_dossier'] ?>"
                               class="btn-delete"
                               onclick="return confirm('Delete this dossier?')">
                                <i class="fa fa-trash"></i>
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