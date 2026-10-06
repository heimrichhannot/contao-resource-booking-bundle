<?php

namespace HeimrichHannot\ResourceBookingBundle\Controller;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use HeimrichHannot\ResourceBookingBundle\Booking\Query\BlockingBookingQuery;
use HeimrichHannot\ResourceBookingBundle\Contao\Table;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/_huh_rb', name: 'huh_rb.', defaults: ['_scope' => 'frontend'])]
class ApiController extends AbstractController
{
    private const MAX_IDS = 50;

    public function __construct(
        private readonly Connection           $connection,
        private readonly BlockingBookingQuery $blockingBookingQuery,
    ) {}

    #[Route('/bookings/{bookingArchive}', name: 'bookings', requirements: ['bookingArchive' => '\d{1,10}'], methods: ['GET'])]
    public function getBookings(int $bookingArchive): Response
    {
        if ($bookingArchive < 1) {
            return $this->json(['error' => 'Invalid booking archive ID'], Response::HTTP_BAD_REQUEST);
        }

        $published = $this->connection->fetchOne(
            'SELECT published FROM ' . Table::BOOKING_ARCHIVE->value . ' WHERE id = ?',
            [$bookingArchive],
        );

        if (!$published) {
            return $this->json(['error' => 'Booking archive not found'], Response::HTTP_NOT_FOUND);
        }

        $qb = $this->connection->createQueryBuilder()
            ->select('r.id AS resourceId', 'br.quantity AS quantity', 'b.start AS start', 'b.end AS end')
            ->from(Table::BOOKING->value, 'b')
            ->innerJoin('b', Table::BOOKING_RESOURCE->value, 'br', 'b.id = br.pid')
            ->innerJoin('br', Table::RESOURCE->value, 'r', 'br.resourceId = r.id')
            ->where('b.pid = :id')
            ->setParameter('id', $bookingArchive);

        $result = $this->blockingBookingQuery->restrict($qb, 'b')->executeQuery();
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
        if (null === $archiveIds = $this->parseIds($request->query->all()['archive'] ?? null)) {
            return $this->json(['error' => 'No or invalid list provided'], Response::HTTP_BAD_REQUEST);
        }

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
            ->where("published = '1'")
            ->andWhere('id IN (:archiveIds)')
            ->setParameter('archiveIds', $archiveIds, ArrayParameterType::INTEGER)
            ->executeQuery();

        $archives = $result->fetchAllAssociative();

        $result->free();

        // Only list resources of the published archives, never of unpublished ones
        $publishedArchiveIds = $this->connection->fetchFirstColumn(
            'SELECT id FROM ' . Table::RESOURCE_ARCHIVE->value . " WHERE published = '1' AND id IN (?)",
            [$archiveIds],
            [ArrayParameterType::INTEGER],
        );

        $result = $this->connection->createQueryBuilder()
            ->select(\implode(',', \array_map(
                static fn (string $key, string $value): string => "$key AS $value",
                \array_keys($resourceFields),
                $resourceFields,
            )))
            ->from(Table::RESOURCE->value)
            ->where('pid IN (:archiveIds)')
            ->andWhere("published = '1'")
            ->setParameter('archiveIds', \array_map('\intval', $publishedArchiveIds) ?: [0], ArrayParameterType::INTEGER)
            ->executeQuery();

        $resources = $result->fetchAllAssociative();

        $result->free();

        return $this->json([
            'archives' => $archives,
            'resources' => $resources,
        ]);
    }

    /**
     * Accepts a single ID or a list of IDs (archive=1 or archive[]=1&archive[]=2).
     *
     * @return int[]|null The unique IDs, or null if the input is not a valid list of IDs.
     */
    private function parseIds(mixed $value): ?array
    {
        $values = \is_array($value) ? $value : [$value];

        if (!$values || \count($values) > self::MAX_IDS || !\array_is_list($values)) {
            return null;
        }

        $ids = [];

        foreach ($values as $id)
        {
            if (!\is_string($id) || !\preg_match('/^[1-9]\d{0,9}$/', $id)) {
                return null;
            }

            $ids[] = (int) $id;
        }

        $ids = \array_values(\array_unique($ids));
        \sort($ids);

        return $ids;
    }
}
