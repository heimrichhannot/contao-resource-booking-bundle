<?php

namespace HeimrichHannot\ResourceBookingBundle\Tests\Booking\Step;

use HeimrichHannot\ResourceBookingBundle\Booking\Step\InitStep;
use HeimrichHannot\ResourceBookingBundle\Model\BookingArchiveModel;
use HeimrichHannot\ResourceBookingBundle\Model\BookingModel;
use HeimrichHannot\ResourceBookingBundle\Tests\Fixtures\FakeBookingModel;
use PHPUnit\Framework\TestCase;

class InitStepTest extends TestCase
{
    public function testCancelsABookingWhoseReservationExpired(): void
    {
        $result = (new InitStep())->process($this->booking(\time() - 1));

        $this->assertTrue($result->isCancel(), 'An expired reservation is not a processing failure');
        $this->assertStringStartsWith('Reservation expired at ', (string) $result->message());
    }

    public function testContinuesWithABookingWhoseReservationIsValid(): void
    {
        $this->assertTrue((new InitStep())->process($this->booking(\time() + 3600))->isNext());
        $this->assertTrue((new InitStep())->process($this->booking(null))->isNext());
    }

    private function booking(?int $expiresAt): BookingModel
    {
        $booking = new class(['id' => 1, 'status' => '', 'expiresAt' => $expiresAt]) extends FakeBookingModel {
            public ?BookingArchiveModel $archive = null;

            public function getArchive(): ?BookingArchiveModel
            {
                return $this->archive;
            }
        };
        $booking->archive = $this->createMock(BookingArchiveModel::class);

        return $booking;
    }
}
