<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/DossierMedical.php';
require_once __DIR__ . '/../../controllers/DossierMedicalController.php';

$controller = new DossierMedicalController($conn);

// get ID from URL
$id = $_GET['id'];

if ($id) {

    $result = $controller->delete($id);

    if ($result) {
        header("Location: dossierList.php");
        exit;
    } else {
        echo "❌ Delete failed";
    }

} else {
    echo "No ID provided";
}