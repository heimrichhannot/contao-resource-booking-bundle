<?php

namespace HeimrichHannot\ResourceBookingBundle\Booking\Factory;

use Doctrine\DBAL\Connection;
use HeimrichHannot\ResourceBookingBundle\Contao\Table;
use HeimrichHannot\ResourceBookingBundle\Event\BookingUuidGeneratedEvent;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class BookingFactory
{
    public function __construct(
        private Connection                        $connection,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function createBookingUuid(array $row): string
    {
        $uuid = Uuid::v4()->toRfc4122();

        $event = $this->eventDispatcher->dispatch(
            new BookingUuidGeneratedEvent(uuid: $uuid, row: $row, testUnique: $this->testUniqueUuid(...))
        );

        if (!$uuid = $event->uuid) {
            throw new \RuntimeException('No UUID generated.');
        }

        if (!$this->testUniqueUuid($uuid)) {
            throw new \RuntimeException('UUID not unique.');
        }

        return $uuid;
    }

    private function testUniqueUuid(string $uuid): bool
    {
        $result = $this->connection->createQueryBuilder()
            ->select('id')
            ->from(Table::BOOKING->value)
            ->where('uuid = :uuid')
            ->setParameter('uuid', $uuid)
            ->setMaxResults(1)
            ->executeQuery();
        $rowCount = $result->rowCount();
        $result->free();
        return $rowCount === 0;
    }
}