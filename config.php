<?php

class Config {

    public static function getConnexion() {
        try {
            return new PDO(
                "mysql:host=localhost;dbname=jumeaunum;charset=utf8",
                "root",
                "",
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
        } catch (Exception $e) {
            die("Erreur connexion: " . $e->getMessage());
        }
    }
}