<?php

namespace HeimrichHannot\ResourceBookingBundle\Tests\Booking\Query;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use HeimrichHannot\ResourceBookingBundle\Booking\Query\BlockingBookingQuery;
use PHPUnit\Framework\TestCase;

class BlockingBookingQueryTest extends TestCase
{
    private Connection $connection;
    private BlockingBookingQuery $query;
    private \DateTimeZone $timezone;

    protected function setUp(): void
    {
        $this->connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        $this->connection->executeStatement('CREATE TABLE tl_rb_booking (id INTEGER PRIMARY KEY, pid INTEGER, start INTEGER, "end" INTEGER, status TEXT, expiresAt TEXT)');
        $this->connection->executeStatement('CREATE TABLE tl_rb_booking_resource (id INTEGER PRIMARY KEY, pid INTEGER, resourceId INTEGER)');

        $this->query = new BlockingBookingQuery($this->connection);
        $this->timezone = new \DateTimeZone('Europe/Berlin');
    }

    public function testBookingOfTheSameArchiveBlocks(): void
    {
        $this->addBooking(archiveId: 1, resourceId: 7, start: '2027-05-10', end: '2027-05-12');

        $this->assertTrue($this->query->hasOverlap(1, [7], $this->day('2027-05-12'), $this->day('2027-05-14')));
    }

    public function testBookingOfAnotherArchiveDoesNotBlock(): void
    {
        $this->addBooking(archiveId: 2, resourceId: 7, start: '2027-05-10', end: '2027-05-12');

        $this->assertFalse($this->query->hasOverlap(1, [7], $this->day('2027-05-10'), $this->day('2027-05-12')));
    }

    public function testAdjacentDaysDoNotBlock(): void
    {
        $this->addBooking(archiveId: 1, resourceId: 7, start: '2027-05-10', end: '2027-05-12');

        $this->assertFalse($this->query->hasOverlap(1, [7], $this->day('2027-05-13'), $this->day('2027-05-14')));
    }

    private function day(string $date): \DateTimeImmutable
    {
        return new \DateTimeImmutable($date, $this->timezone);
    }

    private function addBooking(int $archiveId, int $resourceId, string $start, string $end): void
    {
        $this->connection->executeStatement(
            'INSERT INTO tl_rb_booking (pid, start, "end", status, expiresAt) VALUES (?, ?, ?, \'\', \'\')',
            [$archiveId, $this->day($start)->getTimestamp(), $this->day($end)->getTimestamp()],
        );

        $this->connection->insert('tl_rb_booking_resource', [
            'pid' => (int) $this->connection->lastInsertId(),
            'resourceId' => $resourceId,
        ]);
    }
}
