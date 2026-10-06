<?php

namespace HeimrichHannot\ResourceBookingBundle\Migration;

use Contao\CoreBundle\Migration\AbstractMigration;
use Contao\CoreBundle\Migration\MigrationResult;
use Contao\StringUtil;
use Doctrine\DBAL\Connection;
use HeimrichHannot\ResourceBookingBundle\Booking\Step\OptInStep;
use HeimrichHannot\ResourceBookingBundle\Contao\Table;

/**
 * Unconfirmed bookings used to store their reservation expiry only in the internal state, which nothing evaluated, so
 * they blocked their period forever. Copies the expiry to the expiresAt column that is now checked.
 */
class ReservationExpiryMigration extends AbstractMigration
{
    public function __construct(
        private readonly Connection $connection,
    ) {}

    public function shouldRun(): bool
    {
        $schemaManager = $this->connection->createSchemaManager();

        if (!$schemaManager->tablesExist([Table::BOOKING->value])) {
            return false;
        }

        $columns = \array_change_key_case($schemaManager->listTableColumns(Table::BOOKING->value));

        if (!isset($columns['expiresat'], $columns['internalstate'], $columns['status'])) {
            return false;
        }

        return (bool) $this->connection->fetchOne($this->selectAffected('COUNT(*)'), [OptInStep::getName()]);
    }

    public function run(): MigrationResult
    {
        $rows = $this->connection->fetchAllAssociative(
            $this->selectAffected('id, tstamp, internalState'),
            [OptInStep::getName()],
        );

        foreach ($rows as $row)
        {
            $state = StringUtil::deserialize($row['internalState'] ?? null, true);
            $expiresAt = (int) ($state['reservationExpiresAt'] ?? $state['optInExpiresAt'] ?? 0)
                ?: (int) $row['tstamp'] + OptInStep::RESERVATION_TTL;

            $this->connection->update(Table::BOOKING->value, ['expiresAt' => (string) $expiresAt], ['id' => $row['id']]);
        }

        return $this->createResult(true, \sprintf('Set the reservation expiry of %d unconfirmed bookings.', \count($rows)));
    }

    private function selectAffected(string $columns): string
    {
        return "SELECT $columns FROM " . Table::BOOKING->value . " WHERE status = ? AND (expiresAt IS NULL OR expiresAt = '')";
    }
}
