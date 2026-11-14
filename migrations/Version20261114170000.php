<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20261114170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store the studio sign-in name as a username.';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->getTable('station_user');
        $table->dropIndex('uniq_station_user_email');
        $table->renameColumn('email', 'username');
        $table->addUniqueIndex(['username'], 'uniq_station_user_username');
    }

    public function down(Schema $schema): void
    {
        $table = $schema->getTable('station_user');
        $table->dropIndex('uniq_station_user_username');
        $table->renameColumn('username', 'email');
        $table->addUniqueIndex(['email'], 'uniq_station_user_email');
    }
}
