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
    #[Route('/calendar/{bookingArchive}/bookings', name: 'bookings', methods: ['GET'])]
    public function getBookings(Request $request, BookingArchiveModel $bookingArchive): Response
    {
        return $this->json([]);
    }
}