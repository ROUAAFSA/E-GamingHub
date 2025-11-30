<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251130101638 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE commande (id INT AUTO_INCREMENT NOT NULL, date DATETIME NOT NULL, montant NUMERIC(10, 2) NOT NULL, statut VARCHAR(50) NOT NULL, adresse_livraison VARCHAR(255) NOT NULL, methode_livraison VARCHAR(100) NOT NULL, methode_paiement VARCHAR(100) NOT NULL, notes_internes LONGTEXT DEFAULT NULL, numero_pdf VARCHAR(255) DEFAULT NULL, code_promo VARCHAR(100) DEFAULT NULL, utilisateur_id INT NOT NULL, INDEX IDX_6EEAA67DFB88E14F (utilisateur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE commentaire (id INT AUTO_INCREMENT NOT NULL, contenu LONGTEXT NOT NULL, date_creation DATETIME NOT NULL, auteur VARCHAR(255) NOT NULL, signaled TINYINT(1) NOT NULL, evenement_id INT DEFAULT NULL, INDEX IDX_67F068BCFD02F13 (evenement_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE evenement (id INT AUTO_INCREMENT NOT NULL, titre VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, image_name VARCHAR(255) DEFAULT NULL, categorie VARCHAR(30) NOT NULL, updated_at DATETIME NOT NULL, like_count INT NOT NULL, signaled TINYINT(1) NOT NULL, dislike_count INT NOT NULL, enable TINYINT(1) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE panier (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE reservationssalle (id INT AUTO_INCREMENT NOT NULL, date_debut DATETIME NOT NULL, date_fin DATETIME NOT NULL, statut VARCHAR(50) NOT NULL, salle_id INT NOT NULL, utilisateur_id INT DEFAULT NULL, INDEX IDX_7D866BB6DC304035 (salle_id), INDEX IDX_7D866BB6FB88E14F (utilisateur_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE salle (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, capacite INT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE commande ADD CONSTRAINT FK_6EEAA67DFB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE commentaire ADD CONSTRAINT FK_67F068BCFD02F13 FOREIGN KEY (evenement_id) REFERENCES evenement (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE reservationssalle ADD CONSTRAINT FK_7D866BB6DC304035 FOREIGN KEY (salle_id) REFERENCES salle (id)');
        $this->addSql('ALTER TABLE reservationssalle ADD CONSTRAINT FK_7D866BB6FB88E14F FOREIGN KEY (utilisateur_id) REFERENCES utilisateur (id)');
        $this->addSql('ALTER TABLE utilisateur ADD is_blocked TINYINT(1) NOT NULL, ADD face_descriptor LONGTEXT DEFAULT NULL, ADD reset_token VARCHAR(255) DEFAULT NULL, ADD reset_token_expires_at DATETIME DEFAULT NULL, ADD remember_me_token VARCHAR(255) DEFAULT NULL, CHANGE type role VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE commande DROP FOREIGN KEY FK_6EEAA67DFB88E14F');
        $this->addSql('ALTER TABLE commentaire DROP FOREIGN KEY FK_67F068BCFD02F13');
        $this->addSql('ALTER TABLE reservationssalle DROP FOREIGN KEY FK_7D866BB6DC304035');
        $this->addSql('ALTER TABLE reservationssalle DROP FOREIGN KEY FK_7D866BB6FB88E14F');
        $this->addSql('DROP TABLE commande');
        $this->addSql('DROP TABLE commentaire');
        $this->addSql('DROP TABLE evenement');
        $this->addSql('DROP TABLE panier');
        $this->addSql('DROP TABLE reservationssalle');
        $this->addSql('DROP TABLE salle');
        $this->addSql('ALTER TABLE utilisateur DROP is_blocked, DROP face_descriptor, DROP reset_token, DROP reset_token_expires_at, DROP remember_me_token, CHANGE role type VARCHAR(255) NOT NULL');
    }
}
