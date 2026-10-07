<?php

namespace HeimrichHannot\ResourceBookingBundle\Tests\EventListener\DataContainer;

use Contao\Controller;
use Contao\CoreBundle\DataContainer\DataContainerOperation;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\CoreBundle\Framework\Adapter;
use Contao\CoreBundle\Framework\ContaoFramework;
use Contao\DataContainer;
use Contao\Message;
use Contao\System;
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
use HeimrichHannot\ResourceBookingBundle\Tests\Fixtures\FakeBookingModel;
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

    /** Backend messages as [type, translated message] */
    private array $messages = [];

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

    public function testDoesNotProcessABookingWhoseProcessingDidNotFail(): void
    {
        $booking = new FakeBookingModel(['id' => 1, 'processingFailed' => '']);

        $this->processRequest($booking, StepResult::wait());

        $this->assertSame(0, $this->runs);
        $this->assertSame(0, $booking->saves);
        $this->assertSame([['error', 'backend.process_not_failed {"%id%":1}']], $this->messages);
    }

    public function testProcessesABookingWhoseProcessingFailed(): void
    {
        $booking = new FakeBookingModel(['id' => 1, 'processingFailed' => '1']);

        $this->processRequest($booking, StepResult::wait());

        $this->assertSame(1, $this->runs);
        $this->assertFalse($booking->processingFailed);
        $this->assertSame([['confirmation', 'backend.process_succeeded {"%id%":1}']], $this->messages);
    }

    public function testEscapesTheFailureReason(): void
    {
        $booking = new FakeBookingModel(['id' => 1, 'processingFailed' => '1']);

        $this->processRequest($booking, StepResult::error('<b>"x"</b>'));

        $this->assertSame(1, $this->runs);
        $this->assertSame('error', $this->messages[0][0]);
        $this->assertStringContainsString('&lt;b&gt;&quot;x&quot;&lt;\\/b&gt;', $this->messages[0][1]);
    }

    public function testReportsABookingThatWasNotProcessedFurther(): void
    {
        $booking = new FakeBookingModel(['id' => 1, 'processingFailed' => '1']);

        $this->processRequest($booking, StepResult::cancel('Reservation expired'));

        $this->assertFalse($booking->processingFailed);
        $this->assertSame([['info', 'backend.process_cancelled {"%id%":1,"%reason%":"Reservation expired"}']], $this->messages);
    }

    private function processRequest(BookingModel $booking, StepResult $stepResult): void
    {
        $request = new Request(['key' => 'process', 'id' => (string) $booking->id, 'rt' => 'valid']);

        $this->listener($request, booking: $booking, stepResult: $stepResult)->processOnRequest();
    }

    private function listener(
        Request       $request,
        bool          $tokenValid = true,
        ?BookingModel $booking = null,
        ?StepResult   $stepResult = null,
    ): ProcessBookingOperationListener {
        $requestStack = new RequestStack();
        $requestStack->push($request);

        $tokenManager = $this->createMock(CsrfTokenManagerInterface::class);
        $tokenManager->method('isTokenValid')->willReturn($tokenValid);

        $connection = DriverManager::getConnection(['driver' => 'pdo_sqlite', 'memory' => true]);
        $step = new class(fn () => ++$this->runs, $stepResult ?? StepResult::wait()) implements BookingStepInterface {
            public function __construct(private readonly \Closure $onRun, private readonly StepResult $result) {}

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

                return $this->result;
            }
        };

        // Returns the key and its parameters, so tests can check both
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(
            static fn (string $id, array $parameters = []): string => $id . ' ' . \json_encode($parameters)
        );

        return new ProcessBookingOperationListener(
            new BookingProcessor(
                new BookingPipeline([$step]),
                new BookingFactory($connection, $this->createMock(EventDispatcherInterface::class), new BookingPayloadParser(), new BlockingBookingQuery($connection)),
                new NullLogger(),
            ),
            $this->framework($booking),
            $requestStack,
            $translator,
            $tokenManager,
            'contao_csrf_token',
        );
    }

    private function framework(?BookingModel $booking): ContaoFramework
    {
        $models = $this->adapter(['findByPk']);
        $models->method('findByPk')->willReturn($booking);

        $message = $this->adapter(['addError', 'addConfirmation', 'addInfo']);
        $message->method('addError')->willReturnCallback(function (string $text): void { $this->messages[] = ['error', $text]; });
        $message->method('addConfirmation')->willReturnCallback(function (string $text): void { $this->messages[] = ['confirmation', $text]; });
        $message->method('addInfo')->willReturnCallback(function (string $text): void { $this->messages[] = ['info', $text]; });

        $adapters = [
            BookingModel::class => $models,
            Message::class => $message,
            Controller::class => $this->adapter(['redirect']),
            System::class => $this->adapter(['getReferer']),
        ];

        $framework = $this->createMock(ContaoFramework::class);
        $framework->method('getAdapter')->willReturnCallback(static fn (string $class): Adapter => $adapters[$class]);

        return $framework;
    }

    private function adapter(array $methods): Adapter
    {
        return $this->getMockBuilder(Adapter::class)->disableOriginalConstructor()->addMethods($methods)->getMock();
    }

    private function operation(array $record): DataContainerOperation
    {
        return new DataContainerOperation('process', ['href' => 'key=process', 'icon' => 'sync.svg'], $record, $this->createMock(DataContainer::class));
    }
}
