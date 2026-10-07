<?php

namespace HeimrichHannot\ResourceBookingBundle\Tests\Util;

use HeimrichHannot\ResourceBookingBundle\Util\EmailAddress;
use PHPUnit\Framework\TestCase;
use Terminal42\NotificationCenterBundle\Util\Email;

class EmailAddressTest extends TestCase
{
    /**
     * @dataProvider singleAddresses
     */
    public function testAcceptsASinglePlainAddress(string $email): void
    {
        $this->assertTrue(EmailAddress::isSingle($email));
        $this->assertSame([$email], Email::splitEmailAddresses($email), 'The Notification Center sends to exactly this address');
    }

    public static function singleAddresses(): iterable
    {
        yield 'simple' => ['max@example.org'];
        yield 'dots and tag' => ['max.muster+tag@sub.example.de'];
    }

    /**
     * @dataProvider otherInputs
     */
    public function testRejectsAnythingButASinglePlainAddress(string $email): void
    {
        $this->assertFalse(EmailAddress::isSingle($email));
    }

    public static function otherInputs(): iterable
    {
        yield 'commas in a quoted local part' => ['"a,victim@evil.example,b"@sas.example'];
        yield 'quoted local part with a space' => ['"x y"@example.org'];
        yield 'comma separated list' => ['a@example.org,b@example.org'];
        yield 'semicolon separated list' => ['a@example.org;b@example.org'];
        yield 'friendly name' => ['Max <max@example.org>'];
        yield 'leading space' => [' max@example.org'];
        yield 'line break' => ["max@example.org\nBcc: victim@evil.example"];
        yield 'no address' => ['max'];
        yield 'empty' => [''];
    }
}
