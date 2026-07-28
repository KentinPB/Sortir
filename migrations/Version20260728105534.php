<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260728XXXXXXXX extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Conversion des tables vers le moteur InnoDB pour supporter les clés étrangères';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE campus ENGINE = InnoDB');
        $this->addSql('ALTER TABLE etat ENGINE = InnoDB');
        $this->addSql('ALTER TABLE lieu ENGINE = InnoDB');
        $this->addSql('ALTER TABLE participant ENGINE = InnoDB');
        $this->addSql('ALTER TABLE sortie ENGINE = InnoDB');
        $this->addSql('ALTER TABLE sortie_participant ENGINE = InnoDB');
        $this->addSql('ALTER TABLE ville ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // Pas nécessaire de revenir en MyISAM
    }
}
