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

        return (bool) $this->findExpiries();
    }

    public function run(): MigrationResult
    {
        $expiries = $this->findExpiries();

        foreach ($expiries as $id => $expiresAt) {
            $this->connection->update(Table::BOOKING->value, ['expiresAt' => (string) $expiresAt], ['id' => $id]);
        }

        return $this->createResult(true, \sprintf('Set the reservation expiry of %d unconfirmed bookings.', \count($expiries)));
    }

    /**
     * Bookings still waiting for their opt-in confirmation, with the expiry they get.
     *
     * Bookings whose opt-in was confirmed are left out: they must keep blocking their period, even if their pipeline did
     * not get past the opt-in step.
     *
     * @return array<int, int> Expiry timestamp by booking ID
     */
    private function findExpiries(): array
    {
        $rows = $this->connection->fetchAllAssociative(
            'SELECT id, tstamp, internalState FROM ' . Table::BOOKING->value . " WHERE status = ? AND (expiresAt IS NULL OR expiresAt = '')",
            [OptInStep::getName()],
        );

        $expiries = [];

        foreach ($rows as $row)
        {
            $state = StringUtil::deserialize($row['internalState'] ?? null, true);

            if (!empty($state['optedInAt'])) {
                continue;
            }

            $expiries[(int) $row['id']] = (int) ($state['reservationExpiresAt'] ?? $state['optInExpiresAt'] ?? 0)
                ?: (int) $row['tstamp'] + OptInStep::RESERVATION_TTL;
        }

        return $expiries;
    }
}
