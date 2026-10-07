<?php

namespace HeimrichHannot\ResourceBookingBundle\Tests\Booking\Pipeline;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use HeimrichHannot\ResourceBookingBundle\Booking\Factory\BookingFactory;
use HeimrichHannot\ResourceBookingBundle\Booking\Payload\BookingPayloadParser;
use HeimrichHannot\ResourceBookingBundle\Booking\Pipeline\BookingPipeline;
use HeimrichHannot\ResourceBookingBundle\Booking\Pipeline\SubmittedBookingProcessor;
use HeimrichHannot\ResourceBookingBundle\Booking\Query\BlockingBookingQuery;
use HeimrichHannot\ResourceBookingBundle\Booking\Step\BookingStepInterface;
use HeimrichHannot\ResourceBookingBundle\Booking\StepResult;
use HeimrichHannot\ResourceBookingBundle\Model\BookingModel;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class SubmittedBookingProcessorTest extends TestCase
{
    private Connection $connection;

    protected function setUp(): void
    {
        $this->connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        $this->connection->executeStatement('CREATE TABLE tl_rb_booking (id INTEGER PRIMARY KEY, pid INTEGER)');
        $this->connection->executeStatement('CREATE TABLE tl_rb_booking_resource (id INTEGER PRIMARY KEY, pid INTEGER, resourceId INTEGER)');

        $this->connection->insert('tl_rb_booking', ['id' => 1, 'pid' => 1]);
        $this->connection->insert('tl_rb_booking_resource', ['pid' => 1, 'resourceId' => 7]);
    }

    public function testKeepsABookingThatWaitsForTheNextStep(): void
    {
        $processor = $this->processor(static fn (): StepResult => StepResult::wait('Sent opt-in request email.'));

        $this->assertTrue($processor->process($this->booking()));
        $this->assertBookingCount(1);
    }

    public function testDiscardsABookingWhoseProcessingReturnsAnError(): void
    {
        $processor = $this->processor(static fn (): StepResult => StepResult::error('Failed to send review request'));

        $this->assertFalse($processor->process($this->booking()));
        $this->assertBookingCount(0);
    }

    public function testDiscardsABookingWhoseProcessingThrows(): void
    {
        $processor = $this->processor(static fn (): StepResult => throw new \RuntimeException('Mail server unavailable'));

        $this->assertFalse($processor->process($this->booking()));
        $this->assertBookingCount(0);
    }

    /**
     * @param \Closure(BookingModel): StepResult $process
     */
    private function processor(\Closure $process): SubmittedBookingProcessor
    {
        $step = new class($process) implements BookingStepInterface {
            public function __construct(private readonly \Closure $process) {}

            public static function getName(): string
            {
                return 'test';
            }

            public function getPriority(): int
            {
                return 0;
            }

            public function applies(BookingModel $booking): bool
            {
                return true;
            }

            public function process(BookingModel $booking): StepResult
            {
                return ($this->process)($booking);
            }
        };

        return new SubmittedBookingProcessor(
            new BookingPipeline([$step]),
            new BookingFactory(
                $this->connection,
                $this->createMock(EventDispatcherInterface::class),
                new BookingPayloadParser(),
                new BlockingBookingQuery($this->connection),
            ),
            new NullLogger(),
        );
    }

    private function booking(): BookingModel
    {
        $booking = $this->createMock(BookingModel::class);
        $booking->method('__get')->willReturnCallback(static fn (string $key): mixed => $key === 'id' ? 1 : null);

        return $booking;
    }

    private function assertBookingCount(int $expected): void
    {
        $this->assertSame($expected, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM tl_rb_booking'));
        $this->assertSame($expected, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM tl_rb_booking_resource'));
    }
}
