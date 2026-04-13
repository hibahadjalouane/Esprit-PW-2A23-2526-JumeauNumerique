<?php

require_once "../config.php";
require_once "../Model/User.php";

$pdo = Config::getConnexion();
$model = new User($pdo);

$model->add([
    "id_user" => $_POST['id_user'],
    "nom" => $_POST['nom'],
    "prenom" => $_POST['prenom'],
    "email" => $_POST['email'],
    "mot_de_passe" => $_POST['mot_de_passe'],
    "statut_cmpt" => $_POST['statut_cmpt'],
    "cin" => $_POST['cin'],
    "service" => $_POST['service'],
    "id_role" => $_POST['id_role']
]);

header("Location: ../View/user/list.php");