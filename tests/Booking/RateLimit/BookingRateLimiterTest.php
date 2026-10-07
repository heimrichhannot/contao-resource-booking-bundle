<?php

namespace HeimrichHannot\ResourceBookingBundle\Tests\Booking\RateLimit;

use HeimrichHannot\ResourceBookingBundle\Booking\RateLimit\BookingRateLimiter;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Lock\LockFactory;
use Symfony\Component\Lock\Store\InMemoryStore;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;

class BookingRateLimiterTest extends TestCase
{
    public function testCheckingAnEmailDoesNotUseUpItsLimit(): void
    {
        $limiter = $this->limiter(emailLimit: 3);

        for ($i = 0; $i < 10; ++$i) {
            $slot = $limiter->acquireEmailSlot('visitor@example.org');
            $this->assertNotNull($slot);
            $slot->release();
        }
    }

    public function testBlocksAnEmailAfterTheLimitOfCreatedBookings(): void
    {
        $limiter = $this->limiter(emailLimit: 3);

        for ($i = 0; $i < 3; ++$i) {
            $slot = $limiter->acquireEmailSlot('visitor@example.org');
            $this->assertNotNull($slot);
            $limiter->recordBooking('visitor@example.org');
            $slot->release();
        }

        $this->assertNull($limiter->acquireEmailSlot('visitor@example.org'));
        $this->assertNull($limiter->acquireEmailSlot(' Visitor@Example.org '), 'Email addresses are compared case-insensitively and trimmed');
        $this->assertNotNull($limiter->acquireEmailSlot('other@example.org'));
    }

    public function testRejectsAParallelSubmissionForTheSameEmail(): void
    {
        $limiter = $this->limiter(emailLimit: 3);

        // First submission passed the check and is still creating its booking
        $slot = $limiter->acquireEmailSlot('visitor@example.org');
        $this->assertNotNull($slot);

        $this->assertNull($limiter->acquireEmailSlot(' Visitor@Example.org '), 'A parallel submission must not pass the check');
        $this->assertNotNull($limiter->acquireEmailSlot('other@example.org'));

        $slot->release();

        $this->assertNotNull($limiter->acquireEmailSlot('visitor@example.org'));
    }

    public function testLimitsAttemptsPerClient(): void
    {
        $limiter = $this->limiter(clientLimit: 2);

        $this->assertTrue($limiter->consumeAttempt('203.0.113.1'));
        $this->assertTrue($limiter->consumeAttempt('203.0.113.1'));
        $this->assertFalse($limiter->consumeAttempt('203.0.113.1'));
        $this->assertTrue($limiter->consumeAttempt('203.0.113.2'));
    }

    private function limiter(int $clientLimit = 5, int $emailLimit = 3): BookingRateLimiter
    {
        $config = static fn (string $id, int $limit): array => ['id' => $id, 'policy' => 'sliding_window', 'limit' => $limit, 'interval' => '1 hour'];

        return new BookingRateLimiter(
            new RateLimiterFactory($config('client', $clientLimit), new InMemoryStorage()),
            new RateLimiterFactory($config('email', $emailLimit), new InMemoryStorage()),
            new LockFactory(new InMemoryStore()),
        );
    }
}
