<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20261101120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create station_user for the hidden admin login.';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable('station_user');
        $table->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
        $table->addColumn('email', Types::STRING, ['length' => 180]);
        $table->addColumn('role', Types::STRING, ['length' => 32]);
        $table->addColumn('password', Types::STRING, ['length' => 255]);
        $table->setPrimaryKey(['id']);
        $table->addUniqueIndex(['email'], 'uniq_station_user_email');
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('station_user');
    }
}
