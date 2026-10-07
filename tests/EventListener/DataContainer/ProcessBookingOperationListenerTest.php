<?php

namespace HeimrichHannot\ResourceBookingBundle\Tests\EventListener\DataContainer;

use Contao\CoreBundle\DataContainer\DataContainerOperation;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\DataContainer;
use Doctrine\DBAL\DriverManager;
use HeimrichHannot\ResourceBookingBundle\Booking\Factory\BookingFactory;
use HeimrichHannot\ResourceBookingBundle\Booking\Payload\BookingPayloadParser;
use HeimrichHannot\ResourceBookingBundle\Booking\Pipeline\BookingPipeline;
use HeimrichHannot\ResourceBookingBundle\Booking\Pipeline\BookingProcessor;
use HeimrichHannot\ResourceBookingBundle\Booking\Query\BlockingBookingQuery;
use HeimrichHannot\ResourceBookingBundle\Booking\Step\BookingStepInterface;
use HeimrichHannot\ResourceBookingBundle\Booking\StepResult;
use HeimrichHannot\ResourceBookingBundle\EventListener\DataContainer\ProcessBookingOperationListener;
use HeimrichHannot\ResourceBookingBundle\Model\BookingModel;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProcessBookingOperationListenerTest extends TestCase
{
    /** Number of pipeline runs, i.e. how often a booking was processed */
    private int $runs = 0;

    public function testShowsTheOperationOnlyForBookingsWhoseProcessingFailed(): void
    {
        $listener = $this->listener(new Request());

        $failed = $this->operation(['id' => 1, 'processingFailed' => '1']);
        $listener->showOnlyForFailedBookings($failed);
        $this->assertNull($failed->getHtml(), 'Contao renders the operation as configured');

        $fine = $this->operation(['id' => 2, 'processingFailed' => '']);
        $listener->showOnlyForFailedBookings($fine);
        $this->assertSame('', $fine->getHtml(), 'An empty HTML hides the operation');
    }

    public function testIgnoresRequestsForOtherActions(): void
    {
        $this->listener(new Request(['act' => 'edit', 'id' => '1']))->processOnRequest();

        $this->assertSame(0, $this->runs);
    }

    public function testRejectsAProcessRequestWithoutAValidRequestToken(): void
    {
        $listener = $this->listener(new Request(['key' => 'process', 'id' => '1', 'rt' => 'forged']), tokenValid: false);

        try {
            $listener->processOnRequest();
            $this->fail('Expected an AccessDeniedException');
        } catch (AccessDeniedException) {
        }

        $this->assertSame(0, $this->runs);
    }

    private function listener(Request $request, bool $tokenValid = true): ProcessBookingOperationListener
    {
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $tokenManager = $this->createMock(CsrfTokenManagerInterface::class);
        $tokenManager->method('isTokenValid')->willReturn($tokenValid);

        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        $step = new class(fn () => ++$this->runs) implements BookingStepInterface {
            public function __construct(private readonly \Closure $onRun) {}

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
                ($this->onRun)();

                return StepResult::wait();
            }
        };

        return new ProcessBookingOperationListener(
            new BookingProcessor(
                new BookingPipeline([$step]),
                new BookingFactory($connection, $this->createMock(EventDispatcherInterface::class), new BookingPayloadParser(), new BlockingBookingQuery($connection)),
                new NullLogger(),
            ),
            $this->createMock(ContaoFramework::class),
            $requestStack,
            $this->createMock(TranslatorInterface::class),
            $tokenManager,
            'contao_csrf_token',
        );
    }

    private function operation(array $record): DataContainerOperation
    {
        return new DataContainerOperation('process', ['href' => 'key=process', 'icon' => 'sync.svg'], $record, $this->createMock(DataContainer::class));
    }
}
