<?php

require_once __DIR__ . "/../config.php";
require_once __DIR__ . "/../Model/User.php";

class UserController {

    private $model;

    public function __construct($pdo) {
        $this->model = new User($pdo);
    }

    public function list() {
        return $this->model->getAll();
    }

    public function roles() {
        return $this->model->getRoles();
    }
}