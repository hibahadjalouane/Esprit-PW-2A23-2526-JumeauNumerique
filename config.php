<?php

class config
{
    private static $pdo = null;

    public static function getConnexion()
    {
        if (!isset(self::$pdo)) {
            try {
                self::$pdo = new PDO(
<<<<<<< HEAD
                    'mysql:host=localhost;dbname=jumeaunum',
                    'root',
                    '',
                    [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
=======
                    'mysql:host=localhost;dbname=jumeaunum;charset=utf8mb4',
                    'root',
                    '',
                    [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
>>>>>>> main
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                    ]
                );
            } catch (Exception $e) {
                die('Erreur : ' . $e->getMessage());
            }
        }
        return self::$pdo;
    }
}

config::getConnexion();