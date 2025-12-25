<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251223105727 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE categorie (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(255) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE code_promo (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(50) NOT NULL, description VARCHAR(255) NOT NULL, montant_fixe NUMERIC(10, 2) DEFAULT NULL, pourcentage NUMERIC(5, 2) DEFAULT NULL, nb_utilisations_max INT DEFAULT NULL, nb_utilisations INT NOT NULL, nb_utilisations_par_utilisateur INT DEFAULT NULL, date_expiration DATETIME DEFAULT NULL, montant_minimum_panier NUMERIC(10, 2) DEFAULT NULL, actif TINYINT(1) NOT NULL, produit_specifique_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_5C4683B777153098 (code), INDEX IDX_5C4683B785D3C9D6 (produit_specifique_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE ligne_commande (id INT AUTO_INCREMENT NOT NULL, quantite INT NOT NULL, prix_unitaire NUMERIC(10, 2) NOT NULL, commande_id INT NOT NULL, produit_id INT NOT NULL, INDEX IDX_3170B74B82EA2E54 (commande_id), INDEX IDX_3170B74BF347EFB (produit_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE mouvement_stock (id INT AUTO_INCREMENT NOT NULL, quantite INT NOT NULL, type VARCHAR(50) NOT NULL, date DATETIME NOT NULL, commentaire LONGTEXT DEFAULT NULL, produit_id INT NOT NULL, INDEX IDX_61E2C8EBF347EFB (produit_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE promotion (id INT AUTO_INCREMENT NOT NULL, pourcentage NUMERIC(5, 2) NOT NULL, date_debut DATETIME NOT NULL, date_fin DATETIME NOT NULL, actif TINYINT(1) NOT NULL, produit_id INT NOT NULL, INDEX IDX_C11D7DD1F347EFB (produit_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE code_promo ADD CONSTRAINT FK_5C4683B785D3C9D6 FOREIGN KEY (produit_specifique_id) REFERENCES produit (id)');
        $this->addSql('ALTER TABLE ligne_commande ADD CONSTRAINT FK_3170B74B82EA2E54 FOREIGN KEY (commande_id) REFERENCES commande (id)');
        $this->addSql('ALTER TABLE ligne_commande ADD CONSTRAINT FK_3170B74BF347EFB FOREIGN KEY (produit_id) REFERENCES produit (id)');
        $this->addSql('ALTER TABLE mouvement_stock ADD CONSTRAINT FK_61E2C8EBF347EFB FOREIGN KEY (produit_id) REFERENCES produit (id)');
        $this->addSql('ALTER TABLE promotion ADD CONSTRAINT FK_C11D7DD1F347EFB FOREIGN KEY (produit_id) REFERENCES produit (id)');
        $this->addSql('ALTER TABLE produit ADD seuil_alerte INT NOT NULL, ADD categorie_id INT NOT NULL, CHANGE quantite stock INT NOT NULL');
        $this->addSql('ALTER TABLE produit ADD CONSTRAINT FK_29A5EC27BCF5E72D FOREIGN KEY (categorie_id) REFERENCES categorie (id)');
        $this->addSql('CREATE INDEX IDX_29A5EC27BCF5E72D ON produit (categorie_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE code_promo DROP FOREIGN KEY FK_5C4683B785D3C9D6');
        $this->addSql('ALTER TABLE ligne_commande DROP FOREIGN KEY FK_3170B74B82EA2E54');
        $this->addSql('ALTER TABLE ligne_commande DROP FOREIGN KEY FK_3170B74BF347EFB');
        $this->addSql('ALTER TABLE mouvement_stock DROP FOREIGN KEY FK_61E2C8EBF347EFB');
        $this->addSql('ALTER TABLE promotion DROP FOREIGN KEY FK_C11D7DD1F347EFB');
        $this->addSql('DROP TABLE categorie');
        $this->addSql('DROP TABLE code_promo');
        $this->addSql('DROP TABLE ligne_commande');
        $this->addSql('DROP TABLE mouvement_stock');
        $this->addSql('DROP TABLE promotion');
        $this->addSql('ALTER TABLE produit DROP FOREIGN KEY FK_29A5EC27BCF5E72D');
        $this->addSql('DROP INDEX IDX_29A5EC27BCF5E72D ON produit');
        $this->addSql('ALTER TABLE produit ADD quantite INT NOT NULL, DROP stock, DROP seuil_alerte, DROP categorie_id');
    }
}
