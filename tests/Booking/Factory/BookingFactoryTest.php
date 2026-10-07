<?php

namespace HeimrichHannot\ResourceBookingBundle\Tests\Booking\Factory;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use HeimrichHannot\ResourceBookingBundle\Booking\Factory\BookingFactory;
use HeimrichHannot\ResourceBookingBundle\Booking\Payload\BookingPayloadParser;
use HeimrichHannot\ResourceBookingBundle\Booking\Query\BlockingBookingQuery;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class BookingFactoryTest extends TestCase
{
    private Connection $connection;
    private BookingFactory $factory;

    protected function setUp(): void
    {
        $this->connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        $this->connection->executeStatement('CREATE TABLE tl_rb_booking (id INTEGER PRIMARY KEY, pid INTEGER)');
        $this->connection->executeStatement('CREATE TABLE tl_rb_booking_resource (id INTEGER PRIMARY KEY, pid INTEGER, resourceId INTEGER)');

        $this->factory = new BookingFactory(
            $this->connection,
            $this->createMock(EventDispatcherInterface::class),
            new BookingPayloadParser(),
            new BlockingBookingQuery($this->connection),
        );
    }

    public function testDiscardDeletesTheBookingAndOnlyItsResources(): void
    {
        $this->connection->insert('tl_rb_booking', ['id' => 1, 'pid' => 1]);
        $this->connection->insert('tl_rb_booking', ['id' => 2, 'pid' => 1]);
        $this->connection->insert('tl_rb_booking_resource', ['pid' => 1, 'resourceId' => 7]);
        $this->connection->insert('tl_rb_booking_resource', ['pid' => 1, 'resourceId' => 8]);
        $this->connection->insert('tl_rb_booking_resource', ['pid' => 2, 'resourceId' => 7]);

        $this->factory->discard(1);

        $this->assertSame([2], \array_map('\intval', $this->connection->fetchFirstColumn('SELECT id FROM tl_rb_booking')));
        $this->assertSame([2], \array_map('\intval', $this->connection->fetchFirstColumn('SELECT pid FROM tl_rb_booking_resource')));
    }
}
