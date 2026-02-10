<?php

namespace HeimrichHannot\ResourceBookingBundle\Controller;

use HeimrichHannot\ResourceBookingBundle\Model\BookingArchiveModel;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/_huh_rb', name: 'huh_rb.')]
class ApiController extends AbstractController
{
    #[Route('/bookings/{bookingArchive}', name: 'bookings', methods: ['GET'])]
    public function getBookings(Request $request, int $bookingArchive): Response
    {
        return $this->json([]);
    }

    #[Route('/resources/{csvIds}', name: 'resources', methods: ['GET'])]
    public function getResources(Request $request, string $csvIds): Response
    {
        $ids = \array_map('intval', array_filter(explode(',', $csvIds), 'strlen'));
        return $this->json([
            'ids' => $ids,
        ]);
    }
}