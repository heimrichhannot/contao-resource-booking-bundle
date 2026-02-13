<?php

namespace HeimrichHannot\ResourceBookingBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Doctrine\DBAL\Connection;
use HeimrichHannot\ResourceBookingBundle\Booking\Factory\BookingFactory;
use HeimrichHannot\ResourceBookingBundle\Contao\Table;

readonly class BookingListener
{
    public function __construct(
        private Connection               $connection,
        private BookingFactory           $bookingFactory,
    ) {}

    #[AsCallback(Table::BOOKING->value, 'config.oncreate')]
    public function onCreateConfig(string $table, int $id, array $row, DataContainer $dc): void
    {
        if ($table !== Table::BOOKING->value || !$id || !$dc->id || $dc->table !== $table) {
            return;
        }

        if ($row['uuid'] ?? null) {
            return;
        }

        $uuid = $this->bookingFactory->createBookingUuid($row);

        $this->connection->createQueryBuilder()
            ->update(Table::BOOKING->value)
            ->set('uuid', ':uuid')
            ->where('id = :id')
            ->setParameter('uuid', $uuid)
            ->setParameter('id', $id)
            ->executeStatement();
    }
}