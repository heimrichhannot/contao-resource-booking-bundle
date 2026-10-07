<?php

namespace HeimrichHannot\ResourceBookingBundle\Tests\EventListener\DataContainer;

use Contao\CoreBundle\Twig\Finder\FinderFactory;
use Contao\DataContainer;
use HeimrichHannot\ResourceBookingBundle\EventListener\DataContainer\BookingArchiveListener;
use HeimrichHannot\ResourceBookingBundle\Registry\BookingArchiveTypeRegistry;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;

class BookingArchiveListenerTest extends TestCase
{
    private const STORED = ['minAdvanceDays' => 1, 'maxAdvanceDays' => 365];

    public function testAcceptsMaxGreaterThanMin(): void
    {
        $listener = $this->listener(['minAdvanceDays' => '1', 'maxAdvanceDays' => '30']);

        $this->assertSame('30', $listener->validateMaxAdvanceDays('30', $this->dataContainer()));
        $this->assertSame('1', $listener->validateMinAdvanceDays('1', $this->dataContainer()));
    }

    public function testRejectsMaxBelowTheSubmittedMin(): void
    {
        $listener = $this->listener(['minAdvanceDays' => '30', 'maxAdvanceDays' => '10']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('backend.advance_days_invalid');

        $listener->validateMaxAdvanceDays('10', $this->dataContainer());
    }

    public function testRejectsEqualValues(): void
    {
        $listener = $this->listener(['minAdvanceDays' => '10', 'maxAdvanceDays' => '10']);

        $this->expectException(\RuntimeException::class);

        $listener->validateMinAdvanceDays('10', $this->dataContainer());
    }

    public function testComparesWithTheStoredValueIfTheOtherFieldWasNotSubmitted(): void
    {
        $listener = $this->listener([]);

        $this->expectException(\RuntimeException::class);

        // Stored maximum is 365
        $listener->validateMinAdvanceDays('400', $this->dataContainer());
    }

    private function listener(array $post): BookingArchiveListener
    {
        $requestStack = new RequestStack();
        $requestStack->push(Request::create('/contao', 'POST', $post));

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);

        return new BookingArchiveListener(
            $this->createMock(BookingArchiveTypeRegistry::class),
            $this->createMock(FinderFactory::class),
            $translator,
            $requestStack,
        );
    }

    private function dataContainer(): DataContainer
    {
        $dc = $this->createMock(DataContainer::class);
        $dc->method('getCurrentRecord')->willReturn(self::STORED);

        return $dc;
    }
}
