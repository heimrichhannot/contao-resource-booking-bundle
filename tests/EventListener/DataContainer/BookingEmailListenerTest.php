<?php

namespace HeimrichHannot\ResourceBookingBundle\Tests\EventListener\DataContainer;

use Contao\DataContainer;
use HeimrichHannot\ResourceBookingBundle\EventListener\DataContainer\BookingEmailListener;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Translation\TranslatorInterface;

class BookingEmailListenerTest extends TestCase
{
    public function testKeepsASinglePlainAddress(): void
    {
        $this->assertSame('max@example.org', $this->listener()->validateEmail('max@example.org', $this->createMock(DataContainer::class)));
    }

    /**
     * @dataProvider recipientInjections
     */
    public function testRejectsAnAddressThatMailersWouldSplitIntoSeveralRecipients(string $email): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('backend.email_invalid');

        $this->listener()->validateEmail($email, $this->createMock(DataContainer::class));
    }

    public static function recipientInjections(): iterable
    {
        yield 'commas in a quoted local part' => ['"a,victim@evil.example,b"@sas.example'];
        yield 'encoded by Contao' => ['&quot;a,victim@evil.example,b&quot;@sas.example'];
        yield 'comma separated list' => ['max@example.org,victim@evil.example'];
    }

    private function listener(): BookingEmailListener
    {
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnArgument(0);

        return new BookingEmailListener($translator);
    }
}
