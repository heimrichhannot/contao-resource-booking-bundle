<?php

namespace HeimrichHannot\ResourceBookingBundle\Tests\Booking\Pipeline;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use HeimrichHannot\ResourceBookingBundle\Booking\Factory\BookingFactory;
use HeimrichHannot\ResourceBookingBundle\Booking\Payload\BookingPayloadParser;
use HeimrichHannot\ResourceBookingBundle\Booking\Pipeline\BookingPipeline;
use HeimrichHannot\ResourceBookingBundle\Booking\Pipeline\BookingProcessor;
use HeimrichHannot\ResourceBookingBundle\Booking\Query\BlockingBookingQuery;
use HeimrichHannot\ResourceBookingBundle\Booking\Step\BookingStepInterface;
use HeimrichHannot\ResourceBookingBundle\Booking\StepResult;
use HeimrichHannot\ResourceBookingBundle\Model\BookingModel;
use HeimrichHannot\ResourceBookingBundle\Tests\Fixtures\FakeBookingModel;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class BookingProcessorTest extends TestCase
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

        $this->assertTrue($processor->processSubmitted($this->booking()));
        $this->assertBookingCount(1);
    }

    public function testDiscardsABookingWhoseProcessingReturnsAnError(): void
    {
        $processor = $this->processor(static fn (): StepResult => StepResult::error('Failed to send review request'));

        $this->assertFalse($processor->processSubmitted($this->booking()));
        $this->assertBookingCount(0);
    }

    public function testDiscardsABookingWhoseProcessingThrows(): void
    {
        $processor = $this->processor(static fn (): StepResult => throw new \RuntimeException('Mail server unavailable'));

        $this->assertFalse($processor->processSubmitted($this->booking()));
        $this->assertBookingCount(0);
    }

    public function testFlagsAPendingBookingWhoseProcessingReturnsAnError(): void
    {
        $booking = new FakeBookingModel(['id' => 1]);
        $processor = $this->processor(static fn (): StepResult => StepResult::error('Failed to send review request'));

        $this->assertFalse($processor->processPending($booking));
        $this->assertTrue($booking->processingFailed);
        $this->assertSame('Failed to send review request', $booking->get('processingError'));
        $this->assertGreaterThan(0, $booking->saves);
        $this->assertBookingCount(1);
    }

    public function testFlagsAPendingBookingWhoseProcessingThrows(): void
    {
        $booking = new FakeBookingModel(['id' => 1]);
        $processor = $this->processor(static fn (): StepResult => throw new \RuntimeException('Mail server unavailable'));

        $this->assertFalse($processor->processPending($booking));
        $this->assertTrue($booking->processingFailed);
        $this->assertSame('RuntimeException: Mail server unavailable', $booking->get('processingError'));
        $this->assertBookingCount(1);
    }

    public function testClearsTheFlagWhenProcessingSucceedsAgain(): void
    {
        $booking = new FakeBookingModel([
            'id' => 1,
            'processingFailed' => true,
            'internalState' => \serialize(['processingError' => 'Failed to send review request']),
        ]);
        $processor = $this->processor(static fn (): StepResult => StepResult::wait('Waiting for review'));

        $this->assertTrue($processor->processPending($booking));
        $this->assertFalse($booking->processingFailed);
        $this->assertNull($booking->get('processingError'));
        $this->assertGreaterThan(0, $booking->saves);
    }

    /**
     * @param \Closure(BookingModel): StepResult $process
     */
    private function processor(\Closure $process): BookingProcessor
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

        return new BookingProcessor(
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
