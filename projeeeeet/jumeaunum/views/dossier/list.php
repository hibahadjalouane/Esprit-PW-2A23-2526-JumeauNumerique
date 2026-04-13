<?php
require_once '../../config/database.php';

// Page title for topbar
$pageTitle = "Dossiers Médicaux";

// Fetch dossiers
$sql = "SELECT * FROM dossier_medical";
$stmt = $conn->prepare($sql);
$stmt->execute();
$dossiers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Dossiers Médicaux</title>

    <link rel="stylesheet" href="../../public/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>

<div class="container">

    <!-- SIDEBAR -->
    <?php include '../layout/sidebar.php'; ?>

    <!-- MAIN -->
    <div class="main">

        <!-- TOPBAR -->
        <?php include '../layout/topbar.php'; ?>

        <!-- HEADER ACTION -->
        <div style="margin: 15px 0;">
            <a href="add.php" class="btn-add">+ Nouveau Dossier</a>
        </div>

        <!-- TABLE -->
        <div class="table-container">

            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Description</th>
                        <th>Date Création</th>
                        <th>Patient</th>
                        <th>Médecin</th>
                        <th>Actions</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach($dossiers as $d): ?>
                        <tr>
                            <td><?= $d['id_dossier'] ?></td>
                            <td><?= $d['description'] ?></td>
                            <td><?= $d['date_creation'] ?></td>
                            <td><?= $d['id_patient'] ?></td>
                            <td><?= $d['id_medecin'] ?></td>

                            <td>
                                <a href="edit.php?id=<?= $d['id_dossier'] ?>" class="btn-edit">
                                    <i class="fa fa-edit"></i>
                                </a>

                                <a href="delete.php?id=<?= $d['id_dossier'] ?>" class="btn-delete"
                                   onclick="return confirm('Delete this dossier?')">
                                    <i class="fa fa-trash"></i>
                                </a>

                                <a href="details.php?id=<?= $d['id_dossier'] ?>" class="btn-view">
                                    <i class="fa fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>

            </table>

        </div>

    </div>
</div>

</body>
</html>