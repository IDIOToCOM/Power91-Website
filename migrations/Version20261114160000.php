<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

final class Version20261114160000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Store the program schedule and on-air team so the studio board can edit them.';
    }

    public function up(Schema $schema): void
    {
        $slots = $schema->createTable('program_slot');
        $slots->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
        $slots->addColumn('title', Types::STRING, ['length' => 120]);
        $slots->addColumn('host_name', Types::STRING, ['length' => 120]);
        $slots->addColumn('genre', Types::STRING, ['length' => 80]);
        $slots->addColumn('start_hour', Types::INTEGER);
        $slots->addColumn('end_hour', Types::INTEGER);
        $slots->addColumn('weekdays', Types::JSON);
        $slots->addColumn('position', Types::INTEGER);
        $slots->setPrimaryKey(['id']);

        $hosts = $schema->createTable('air_host');
        $hosts->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
        $hosts->addColumn('name', Types::STRING, ['length' => 120]);
        $hosts->addColumn('role', Types::STRING, ['length' => 80]);
        $hosts->addColumn('show_name', Types::STRING, ['length' => 120]);
        $hosts->addColumn('bio', Types::TEXT);
        $hosts->addColumn('initials', Types::STRING, ['length' => 4]);
        $hosts->addColumn('accent', Types::STRING, ['length' => 7]);
        $hosts->addColumn('position', Types::INTEGER);
        $hosts->setPrimaryKey(['id']);

        $markers = $schema->createTable('board_marker');
        $markers->addColumn('marker_key', Types::STRING, ['length' => 32]);
        $markers->setPrimaryKey(['marker_key']);
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable('program_slot');
        $schema->dropTable('air_host');
        $schema->dropTable('board_marker');
    }
}
