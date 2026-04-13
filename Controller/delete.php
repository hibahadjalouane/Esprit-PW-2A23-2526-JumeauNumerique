<?php

require_once "../config.php";
require_once "../Model/User.php";

$pdo = Config::getConnexion();
$model = new User($pdo);

$model->delete($_GET['id']);

header("Location: ../View/user/list.php");