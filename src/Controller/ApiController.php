<?php

namespace HeimrichHannot\ResourceBookingBundle\Controller;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
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
    public function getBookings(Request $request, int $bookingArchive): Response
    {
        return $this->json([]);
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