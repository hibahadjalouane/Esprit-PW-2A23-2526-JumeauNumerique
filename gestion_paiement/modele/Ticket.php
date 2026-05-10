<?php
/**
 * Ticket.php
 * Chemin : gestion_paiement/modele/Ticket.php
 *
 * Classe qui gère la création et la récupération des tickets numériques.
 */

class Ticket
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * Génère un id_ticket unique aléatoire (entier 8 chiffres max)
     * qui n'existe pas encore dans la table ticket_num.
     */
    private function genererIdUnique(): int
    {
        do {
            // Génère un entier entre 10000000 et 99999999
            $id = random_int(10000000, 99999999);
            $stmt = $this->pdo->prepare("SELECT id_ticket FROM ticket_num WHERE id_ticket = :id LIMIT 1");
            $stmt->execute([':id' => $id]);
        } while ($stmt->fetch()); // Recommence si l'id existe déjà

        return $id;
    }

    /**
     * Crée un nouveau ticket pour une facture donnée.
     * Retourne les données du ticket créé ou false en cas d'erreur.
     */
    public function creerTicket(int $id_facture): array|false
    {
        try {
            $id_ticket       = $this->genererIdUnique();
            $date_creation   = date('Y-m-d H:i:s');
            // Expiration = date de création + 15 jours
            $date_expiration = date('Y-m-d H:i:s', strtotime('+15 days'));
            $statut          = 'non utilise';

            $stmt = $this->pdo->prepare("
                INSERT INTO ticket_num (id_ticket, date_creation, date_expiration, statut, id_facture)
                VALUES (:id_ticket, :date_creation, :date_expiration, :statut, :id_facture)
            ");
            $stmt->execute([
                ':id_ticket'       => $id_ticket,
                ':date_creation'   => $date_creation,
                ':date_expiration' => $date_expiration,
                ':statut'          => $statut,
                ':id_facture'      => $id_facture,
            ]);

            return [
                'id_ticket'       => $id_ticket,
                'date_creation'   => $date_creation,
                'date_expiration' => $date_expiration,
                'statut'          => $statut,
                'id_facture'      => $id_facture,
            ];
        } catch (PDOException $e) {
            // On remonte l'erreur pour que payer_facture.php puisse la gérer
            throw $e;
        }
    }

    /**
     * Récupère tous les tickets d'un patient (via ses factures).
     */
    public function getTicketsParPatient(int $id_patient): array
    {
        $stmt = $this->pdo->prepare("
            SELECT
                t.id_ticket,
                t.date_creation,
                t.date_expiration,
                t.statut,
                t.id_facture,
                f.montant,
                tp.nom_type
            FROM ticket_num t
            JOIN facture f ON t.id_facture = f.id_facture
            JOIN type_paiement tp ON f.id_type_paiement = tp.id_type
            WHERE f.id_patient = :id_patient
            ORDER BY t.date_creation DESC
        ");
        $stmt->execute([':id_patient' => $id_patient]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
