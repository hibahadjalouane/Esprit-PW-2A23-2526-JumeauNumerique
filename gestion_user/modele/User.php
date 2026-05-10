<?php
class User {
    private $id_user;
    private $nom;
    private $prenom;
    private $email;
    private $username;
    private $mot_de_passe;
    private $statut_cmpt;
    private $cin;
    private $service;
    private $id_role;

    public function __construct($id_user, $nom, $prenom, $email, $username,
                                $mot_de_passe, $statut_cmpt, $cin, $service, $id_role) {
        $this->id_user      = $id_user;
        $this->nom          = $nom;
        $this->prenom       = $prenom;
        $this->email        = $email;
        $this->username     = $username;
        $this->mot_de_passe = $mot_de_passe;
        $this->statut_cmpt  = $statut_cmpt;
        $this->cin          = $cin;
        $this->service      = $service;
        $this->id_role      = $id_role;
    }

    public function getIdUser()     { return $this->id_user; }
    public function getNom()        { return $this->nom; }
    public function getPrenom()     { return $this->prenom; }
    public function getEmail()      { return $this->email; }
    public function getUsername()   { return $this->username; }
    public function getMdp()        { return $this->mot_de_passe; }
    public function getStatut()     { return $this->statut_cmpt; }
    public function getCin()        { return $this->cin; }
    public function getService()    { return $this->service; }
    public function getIdRole()     { return $this->id_role; }

    public function setIdUser($v)   { $this->id_user = $v; }
    public function setNom($v)      { $this->nom = $v; }
    public function setPrenom($v)   { $this->prenom = $v; }
    public function setEmail($v)    { $this->email = $v; }
    public function setUsername($v) { $this->username = $v; }
    public function setMdp($v)      { $this->mot_de_passe = $v; }
    public function setStatut($v)   { $this->statut_cmpt = $v; }
    public function setCin($v)      { $this->cin = $v; }
    public function setService($v)  { $this->service = $v; }
    public function setIdRole($v)   { $this->id_role = $v; }
}
?>
