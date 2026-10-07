<?php

namespace HeimrichHannot\ResourceBookingBundle\Tests\Migration;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use HeimrichHannot\ResourceBookingBundle\Booking\Step\OptInStep;
use HeimrichHannot\ResourceBookingBundle\Migration\ReservationExpiryMigration;
use PHPUnit\Framework\TestCase;

class ReservationExpiryMigrationTest extends TestCase
{
    private Connection $connection;
    private ReservationExpiryMigration $migration;

    protected function setUp(): void
    {
        $this->connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        $this->connection->executeStatement('CREATE TABLE tl_rb_booking (id INTEGER PRIMARY KEY, tstamp INTEGER, status TEXT, expiresAt TEXT, internalState BLOB)');

        $this->migration = new ReservationExpiryMigration($this->connection);
    }

    public function testCopiesTheStoredExpiryOfUnconfirmedBookings(): void
    {
        $this->addBooking(1, 1700000000, ['optInToken' => 'huhrb-abc', 'reservationExpiresAt' => 1700003600]);

        $this->assertTrue($this->migration->shouldRun());
        $this->migration->run();

        $this->assertSame('1700003600', $this->expiresAt(1));
        $this->assertFalse($this->migration->shouldRun());
    }

    public function testFallsBackToCreationTimePlusTtl(): void
    {
        $this->addBooking(1, 1700000000, []);

        $this->migration->run();

        $this->assertSame((string) (1700000000 + OptInStep::RESERVATION_TTL), $this->expiresAt(1));
    }

    public function testLeavesConfirmedBookingsBlocking(): void
    {
        // Opt-in confirmed, but the pipeline never got past the opt-in step
        $this->addBooking(1, 1600000000, ['reservationExpiresAt' => 1600003600, 'optedInAt' => 1600000500]);

        $this->assertFalse($this->migration->shouldRun());
        $this->migration->run();

        $this->assertNull($this->expiresAt(1));
    }

    private function addBooking(int $id, int $tstamp, array $state): void
    {
        $this->connection->insert('tl_rb_booking', [
            'id' => $id,
            'tstamp' => $tstamp,
            'status' => OptInStep::getName(),
            'expiresAt' => null,
            'internalState' => \serialize($state),
        ]);
    }

    private function expiresAt(int $id): ?string
    {
        $value = $this->connection->fetchOne('SELECT expiresAt FROM tl_rb_booking WHERE id = ?', [$id]);

        return $value === null ? null : (string) $value;
    }
}
