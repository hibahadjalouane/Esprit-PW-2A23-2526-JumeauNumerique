<?php

require_once "../config.php";
require_once "../Model/User.php";

$pdo = Config::getConnexion();
$model = new User($pdo);

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $model->update([
        "id_user" => $_POST['id_user'],
        "nom" => $_POST['nom'],
        "prenom" => $_POST['prenom'],
        "email" => $_POST['email'],
        "cin" => $_POST['cin'],
        "service" => $_POST['service'],
        "id_role" => $_POST['id_role']
    ]);
}

header("Location: ../View/user/list.php");
exit;