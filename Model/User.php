<?php

class User {

    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function getAll() {
        return $this->pdo->query("SELECT * FROM user")->fetchAll();
    }

    public function getRoles() {
        return $this->pdo->query("SELECT * FROM role")->fetchAll();
    }

    public function add($data) {

        $sql = "INSERT INTO user
(id_user, nom, prenom, email, mot_de_passe, statut_cmpt, cin, service, id_role)
VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = $this->pdo->prepare($sql);

$stmt->execute([
    $data['id_user'],
    $data['nom'],
    $data['prenom'],
    $data['email'],
    password_hash($data['mot_de_passe'], PASSWORD_DEFAULT),
    $data['statut_cmpt'],
    $data['cin'],
    $data['service'],
    $data['id_role']
]);
    }

    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM user WHERE id_user=?");
        return $stmt->execute([$id]);
    }

    public function update($data) {
        $sql = "UPDATE user SET 
            nom = ?, 
            prenom = ?, 
            email = ?, 
            cin = ?, 
            service = ?, 
            id_role = ?
            WHERE id_user = ?";

        $stmt = $this->pdo->prepare($sql);

        $stmt->execute([
            $data['nom'],
            $data['prenom'],
            $data['email'],
            $data['cin'],
            $data['service'],
            $data['id_role'],
            $data['id_user']
        ]);
    }
}