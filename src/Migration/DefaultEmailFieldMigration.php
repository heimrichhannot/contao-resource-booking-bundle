<?php

namespace HeimrichHannot\ResourceBookingBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Doctrine\DBAL\Connection;
use HeimrichHannot\ResourceBookingBundle\FormGenerator\ResourceBookingFormType;

class DefaultEmailFieldMigration extends AbstractMigration
{
    private const LEGACY_FIELD = [
        'type' => 'email',
        'name' => '',
        'rgxp' => '',
        'mandatory' => 0,
        'invisible' => 0,
        'value' => '',
        'customTpl' => '',
    ];

    public function __construct(private readonly Connection $connection) {}

    public function shouldRun(): bool
    {
        $schema = $this->connection->createSchemaManager();

        if (!$schema->tablesExist(['tl_form', 'tl_form_field'])) {
            return false;
        }

        $formColumns = \array_change_key_case($schema->listTableColumns('tl_form'));
        $fieldColumns = \array_change_key_case($schema->listTableColumns('tl_form_field'));
        $requiredFields = \array_change_key_case(self::LEGACY_FIELD + ['id' => null, 'pid' => null]);

        return isset($formColumns['id'], $formColumns['formtype'])
            && [] === \array_diff_key($requiredFields, $fieldColumns)
            && [] !== $this->findLegacyFields();
    }

    public function run(): MigrationResult
    {
        $updated = 0;

        foreach ($this->findLegacyFields() as $id) {
            $updated += $this->connection->update(
                'tl_form_field',
                ['type' => 'text', 'name' => 'email', 'rgxp' => 'email', 'mandatory' => 1],
                ['id' => $id] + self::LEGACY_FIELD,
            );
        }

        return $this->createResult(true, \sprintf('Repaired %d default resource booking email fields.', $updated));
    }

    /** @return list<int|string> */
    private function findLegacyFields(): array
    {
        // Only repair an unambiguous wizard field in a form that has no email input.
        $query = $this->connection->createQueryBuilder()
            ->select('MIN(ff.id)')
            ->from('tl_form_field', 'ff')
            ->innerJoin('ff', 'tl_form', 'f', 'f.id = ff.pid')
            ->where('f.formType = :formType')
            ->andWhere(<<<'SQL'
                NOT EXISTS (
                    SELECT 1 FROM tl_form_field existing
                    WHERE existing.pid = ff.pid AND (existing.name = 'email' OR existing.rgxp = 'email')
                )
                SQL)
            ->groupBy('ff.pid')
            ->having('COUNT(*) = 1')
            ->setParameter('formType', ResourceBookingFormType::TYPE);

        foreach (self::LEGACY_FIELD as $column => $value) {
            $query->andWhere("ff.$column = :$column")->setParameter($column, $value);
        }

        return $query->executeQuery()->fetchFirstColumn();
    }
}
