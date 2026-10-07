<?php

namespace HeimrichHannot\ResourceBookingBundle\Tests\Booking\Factory;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use HeimrichHannot\ResourceBookingBundle\Booking\Factory\BookingFactory;
use HeimrichHannot\ResourceBookingBundle\Booking\Payload\BookingPayloadParser;
use HeimrichHannot\ResourceBookingBundle\Booking\Query\BlockingBookingQuery;
use HeimrichHannot\ResourceBookingBundle\Exception\InvalidBookingPayloadException;
use HeimrichHannot\ResourceBookingBundle\Model\BookingArchiveModel;
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

    /**
     * @dataProvider recipientInjections
     */
    public function testRejectsAnEmailAddressThatMailersWouldSplitIntoSeveralRecipients(string $email): void
    {
        $archive = $this->createMock(BookingArchiveModel::class);
        $archive->method('__get')->willReturnCallback(static fn (string $key): mixed => match ($key) {
            'id' => 1,
            'maxAdvanceDays' => 365,
            'maxDurationDays' => 30,
            default => null,
        });

        $day = static fn (string $modify): string => (new \DateTimeImmutable($modify))->format('Y-m-d') . 'T00:00:00.000+00:00';
        $rbData = \json_encode(['resources' => [['id' => 1, 'quantity' => 1]], 'start' => $day('+2 days'), 'end' => $day('+3 days')]);

        $this->expectException(InvalidBookingPayloadException::class);
        $this->expectExceptionMessage('Invalid email address.');

        $this->factory->createFromSubmittedData($archive, ['rb_data' => $rbData, 'email' => $email], [1]);
    }

    public static function recipientInjections(): iterable
    {
        yield 'commas in a quoted local part' => ['"a,victim@evil.example,b"@sas.example'];
        yield 'encoded by Contao' => ['&quot;a,victim@evil.example,b&quot;@sas.example'];
        yield 'comma separated list' => ['max@example.org,victim@evil.example'];
    }
}
