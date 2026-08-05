<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260803161943 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Ne garder QUE les nouvelles modifications :
        $this->addSql('ALTER TABLE participant ADD photo VARCHAR(180) DEFAULT NULL');
        $this->addSql('ALTER TABLE sortie CHANGE motif_annulation motif_annulation LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE participant DROP photo');
        $this->addSql('ALTER TABLE sortie CHANGE motif_annulation motif_annulation LONGTEXT NOT NULL');
    }
}
