<?php

namespace HeimrichHannot\ResourceBookingBundle\Controller;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use HeimrichHannot\ResourceBookingBundle\Booking\FinalState;
use HeimrichHannot\ResourceBookingBundle\Contao\Table;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/_huh_rb', name: 'huh_rb.', defaults: ['_scope' => 'frontend'])]
class ApiController extends AbstractController
{
    public function __construct(
        private readonly Connection $connection,
    ) {}

    #[Route('/bookings/{bookingArchive}', name: 'bookings', methods: ['GET'])]
    public function getBookings(int $bookingArchive): Response
    {
        if ($bookingArchive < 1) {
            return $this->json(['error' => 'Invalid booking archive ID'], Response::HTTP_BAD_REQUEST);
        }

        $result = $this->connection->createQueryBuilder()
            ->select('r.id AS resourceId', 'br.quantity AS quantity', 'b.start AS start', 'b.end AS end')
            ->from(Table::BOOKING->value, 'b')
            ->innerJoin('b', Table::BOOKING_RESOURCE->value, 'br', 'b.id = br.pid')
            ->innerJoin('br', Table::RESOURCE->value, 'r', 'br.resourceId = r.id')
            ->where('b.pid = :id')
            ->andWhere('b.status <> :status')
            ->setParameter('id', $bookingArchive)
            ->setParameter('status', FinalState::REJECTED->value)
            ->executeQuery();
        $bookings = $result->fetchAllAssociative();
        $result->free();

        $byResource = [];
        foreach ($bookings as $booking) {
            $resourceId = $booking['resourceId'];
            unset($booking['resourceId']);
            $byResource[$resourceId] ??= ['resource_id' => $resourceId];
            $byResource[$resourceId]['blocked'][] = $booking;
        }

        \ksort($byResource);
        $byResource = \array_values($byResource);

        return $this->json([
            'bookings' => $byResource,
        ]);
    }

    #[Route('/resources', name: 'resources', methods: ['GET'])]
    public function getResources(Request $request): Response
    {
        $archiveIds = (array) ($request->query->all()['archive'] ?? null);
        $archiveIds = \array_unique(\array_filter(\array_map(static fn (string $id) => (int) \trim($id), $archiveIds)));
        if (!$archiveIds) {
            return $this->json(['error' => 'No or invalid list provided'], Response::HTTP_BAD_REQUEST);
        }
        \sort($archiveIds);

        if (!$archiveFields = \iterator_to_array(Table::RESOURCE_ARCHIVE->select('api'))) {
            return $this->json(['error' => 'No resource archive fields configured for API access'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        if (!$resourceFields = \iterator_to_array(Table::RESOURCE->select('api'))) {
            return $this->json(['error' => 'No resource fields configured for API access'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $result = $this->connection->createQueryBuilder()
            ->select(\implode(', ', \array_map(
                static fn (string $key, string $value): string => "$key AS $value",
                \array_keys($archiveFields),
                $archiveFields,
            )))
            ->from(Table::RESOURCE_ARCHIVE->value)
            ->where('published = 1')
            ->andWhere('id IN (:archiveIds)')
            ->setParameter('archiveIds', $archiveIds, ArrayParameterType::INTEGER)
            ->executeQuery();

        $archives = $result->fetchAllAssociative();

        $result->free();

        $result = $this->connection->createQueryBuilder()
            ->select(\implode(',', \array_map(
                static fn (string $key, string $value): string => "$key AS $value",
                \array_keys($resourceFields),
                $resourceFields,
            )))
            ->from(Table::RESOURCE->value)
            ->where('pid IN (:archiveIds)')
            ->setParameter('archiveIds', $archiveIds, ArrayParameterType::INTEGER)
            ->executeQuery();

        $resources = $result->fetchAllAssociative();

        $result->free();

        return $this->json([
            'archives' => $archives,
            'resources' => $resources,
        ]);
    }
}