<?php

namespace HeimrichHannot\ResourceBookingBundle\Tests\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use HeimrichHannot\ResourceBookingBundle\FormGenerator\ResourceBookingFormType;
use HeimrichHannot\ResourceBookingBundle\Migration\DefaultEmailFieldMigration;
use PHPUnit\Framework\TestCase;

class DefaultEmailFieldMigrationTest extends TestCase
{
    private Connection $connection;
    private DefaultEmailFieldMigration $migration;

    protected function setUp(): void
    {
        $this->connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        $this->migration = new DefaultEmailFieldMigration($this->connection);
    }

    public function testSkipsInstallationsWithoutTheRequiredColumns(): void
    {
        $this->assertFalse($this->migration->shouldRun());

        $this->connection->executeStatement('CREATE TABLE tl_form (id INTEGER PRIMARY KEY)');
        $this->connection->executeStatement('CREATE TABLE tl_form_field (id INTEGER PRIMARY KEY)');

        $this->assertFalse($this->migration->shouldRun());

        $this->connection->executeStatement('ALTER TABLE tl_form ADD formType TEXT');

        $this->assertFalse($this->migration->shouldRun());
    }

    public function testRepairsOnlyUnambiguousWizardFieldsWithoutAnExistingEmailField(): void
    {
        $this->connection->executeStatement('CREATE TABLE tl_form (id INTEGER PRIMARY KEY, formType TEXT)');
        $this->connection->executeStatement(
            "CREATE TABLE tl_form_field (
                id INTEGER PRIMARY KEY, pid INTEGER, type TEXT, name TEXT, label TEXT, rgxp TEXT, mandatory INTEGER,
                invisible INTEGER DEFAULT 0, value TEXT DEFAULT '', customTpl TEXT DEFAULT ''
            )",
        );

        $formIds = range(1, 9);
        foreach ($formIds as $id) {
            $this->connection->insert('tl_form', [
                'id' => $id,
                'formType' => $id === 2 ? '' : ResourceBookingFormType::TYPE,
            ]);
        }

        $legacyField = ['type' => 'email', 'name' => '', 'label' => 'Email address', 'rgxp' => '', 'mandatory' => 0];
        $overrides = [
            3 => ['label' => 'E-Mail-Adresse'],
            5 => ['invisible' => 1],
            6 => ['value' => 'preset@example.org'],
            7 => ['customTpl' => 'form_custom'],
        ];
        foreach ($formIds as $id) {
            $this->connection->insert('tl_form_field', ['id' => $id, 'pid' => $id] + ($overrides[$id] ?? []) + $legacyField);
        }
        $this->connection->insert('tl_form_field', [
            'id' => 10, 'pid' => 4, 'type' => 'text', 'name' => 'email', 'label' => 'Contact', 'rgxp' => '', 'mandatory' => 1,
        ]);
        $this->connection->insert('tl_form_field', ['id' => 11, 'pid' => 8] + $legacyField);
        $this->connection->insert('tl_form_field', [
            'id' => 12, 'pid' => 9, 'type' => 'text', 'name' => 'e_mail', 'label' => 'Contact', 'rgxp' => 'email', 'mandatory' => 1,
        ]);

        $this->assertTrue($this->migration->shouldRun());
        $this->migration->run();

        $definition = $this->connection->fetchAssociative('SELECT type, name, rgxp, mandatory FROM tl_form_field WHERE id = 1');
        $this->assertSame(['type' => 'text', 'name' => 'email', 'rgxp' => 'email', 'mandatory' => 1], $definition);
        $localized = $this->connection->fetchAssociative('SELECT type, name, label FROM tl_form_field WHERE id = 3');
        $this->assertSame(['type' => 'text', 'name' => 'email', 'label' => 'E-Mail-Adresse'], $localized);
        $this->assertSame([2, 4, 5, 6, 7, 8, 9, 11], $this->connection->fetchFirstColumn("SELECT id FROM tl_form_field WHERE type = 'email' ORDER BY id"));
        $this->assertFalse($this->migration->shouldRun());
    }
}
