<?php

namespace HeimrichHannot\ResourceBookingBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use HeimrichHannot\ResourceBookingBundle\Contao\Table;

readonly class BookingResourceListener
{
    public function __construct(
        private Connection $connection,
    ) {}

    #[AsCallback(Table::BOOKING_RESOURCE->value, 'fields.resourceId.options')]
    public function getResourceOptions(?DataContainer $dc): array
    {
        if (!$dc || !$dc->id) {
            return [];
        }

        if (!$bookingId = $dc->getCurrentRecord()['pid'] ?? null) {
            return [];
        }

        $result = $this->connection->createQueryBuilder()
            ->select('DISTINCT(resourceId) as resourceId')
            ->from(Table::BOOKING_RESOURCE->value)
            ->where('pid = :pid')
            ->setParameter('pid', $bookingId)
            ->executeQuery();
        $alreadyBookedResources = $result->fetchFirstColumn();
        $result->free();

        $result = $this->connection->createQueryBuilder()
            ->select('id', 'title')
            ->from(Table::RESOURCE_ARCHIVE->value)
            ->executeQuery();
        $resourceArchives = $result->fetchAllAssociativeIndexed();
        $result->free();

        $result = $this->connection->createQueryBuilder()
            ->select('id', 'title', 'pid')
            ->from(Table::RESOURCE->value)
            ->where('id NOT IN (:excludeIds)')
            ->orWhere('id = :self')
            ->setParameter('excludeIds', $alreadyBookedResources, ArrayParameterType::INTEGER)
            ->setParameter('self', (int) ($dc->value ?: 0))
            ->executeQuery();
        $resources = $result->fetchAllAssociative();
        $result->free();

        $options = [];
        foreach ($resources as $resource) {
            $resourceArchive = $resourceArchives[$resource['pid']] ?? null;
            $options[$resource['id']] = "{$resource['title']} [{$resourceArchive['title']}]";
        }

        return $options;
    }
}