<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Index UNIQUE sur participant.pseudo : l'unicité du pseudo n'était vérifiée
 * que par la validation du formulaire (UniqueEntity), pas par la base.
 *
 * Attention : si des pseudos en double existent déjà, la création de l'index
 * échoue. Les repérer avant de migrer :
 *   SELECT pseudo, COUNT(*) FROM participant GROUP BY pseudo HAVING COUNT(*) > 1;
 */
final class Version20261022123044 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Index unique sur le pseudo des participants';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE UNIQUE INDEX UNIQ_IDENTIFIER_PSEUDO ON participant (pseudo)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX UNIQ_IDENTIFIER_PSEUDO ON participant');
    }
}
