<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Ajout des clés étrangères manquantes.
 *
 * Contexte : sur la base d'origine, les tables étaient en MyISAM, un moteur qui
 * ignore les clés étrangères ; la migration de création (Version20260727141140)
 * ne les avait donc pas réellement posées et celle-ci les ajoutait.
 * Sur une base neuve (InnoDB par défaut), elles existent déjà : les ajouter une
 * seconde fois provoquait l'erreur « Duplicate foreign key constraint name ».
 * Chaque clé n'est donc ajoutée que si elle est absente.
 */
final class Version20260728104535 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout des clés étrangères absentes (bases créées en MyISAM)';
    }

    public function up(Schema $schema): void
    {
        $this->addForeignKeyIfMissing($schema, 'lieu', 'FK_2F577D59A73F0036', 'ALTER TABLE lieu ADD CONSTRAINT FK_2F577D59A73F0036 FOREIGN KEY (ville_id) REFERENCES ville (id)');
        $this->addForeignKeyIfMissing($schema, 'participant', 'FK_D79F6B11AF5D55E1', 'ALTER TABLE participant ADD CONSTRAINT FK_D79F6B11AF5D55E1 FOREIGN KEY (campus_id) REFERENCES campus (id)');
        $this->addForeignKeyIfMissing($schema, 'sortie', 'FK_3C3FD3F2D5E86FF', 'ALTER TABLE sortie ADD CONSTRAINT FK_3C3FD3F2D5E86FF FOREIGN KEY (etat_id) REFERENCES etat (id)');
        $this->addForeignKeyIfMissing($schema, 'sortie', 'FK_3C3FD3F2D7AC6C11', 'ALTER TABLE sortie ADD CONSTRAINT FK_3C3FD3F2D7AC6C11 FOREIGN KEY (site_organisateur_id) REFERENCES campus (id)');
        $this->addForeignKeyIfMissing($schema, 'sortie', 'FK_3C3FD3F2D936B2FA', 'ALTER TABLE sortie ADD CONSTRAINT FK_3C3FD3F2D936B2FA FOREIGN KEY (organisateur_id) REFERENCES participant (id)');
        $this->addForeignKeyIfMissing($schema, 'sortie', 'FK_3C3FD3F26AB213CC', 'ALTER TABLE sortie ADD CONSTRAINT FK_3C3FD3F26AB213CC FOREIGN KEY (lieu_id) REFERENCES lieu (id)');
        $this->addForeignKeyIfMissing($schema, 'sortie_participant', 'FK_E6D4CDADCC72D953', 'ALTER TABLE sortie_participant ADD CONSTRAINT FK_E6D4CDADCC72D953 FOREIGN KEY (sortie_id) REFERENCES sortie (id) ON DELETE CASCADE');
        $this->addForeignKeyIfMissing($schema, 'sortie_participant', 'FK_E6D4CDAD9D1C3019', 'ALTER TABLE sortie_participant ADD CONSTRAINT FK_E6D4CDAD9D1C3019 FOREIGN KEY (participant_id) REFERENCES participant (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE lieu DROP FOREIGN KEY FK_2F577D59A73F0036');
        $this->addSql('ALTER TABLE participant DROP FOREIGN KEY FK_D79F6B11AF5D55E1');
        $this->addSql('ALTER TABLE sortie DROP FOREIGN KEY FK_3C3FD3F2D5E86FF');
        $this->addSql('ALTER TABLE sortie DROP FOREIGN KEY FK_3C3FD3F2D7AC6C11');
        $this->addSql('ALTER TABLE sortie DROP FOREIGN KEY FK_3C3FD3F2D936B2FA');
        $this->addSql('ALTER TABLE sortie DROP FOREIGN KEY FK_3C3FD3F26AB213CC');
        $this->addSql('ALTER TABLE sortie_participant DROP FOREIGN KEY FK_E6D4CDADCC72D953');
        $this->addSql('ALTER TABLE sortie_participant DROP FOREIGN KEY FK_E6D4CDAD9D1C3019');
    }

    /**
     * $schema décrit l'état réel de la base avant la migration : on n'ajoute
     * la contrainte que si la table ne la possède pas encore.
     */
    private function addForeignKeyIfMissing(Schema $schema, string $table, string $name, string $sql): void
    {
        if (!$schema->getTable($table)->hasForeignKey($name)) {
            $this->addSql($sql);
        }
    }
}
