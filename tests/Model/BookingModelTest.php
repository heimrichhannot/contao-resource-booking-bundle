<?php

namespace HeimrichHannot\ResourceBookingBundle\Tests\Model;

use HeimrichHannot\ResourceBookingBundle\Tests\Fixtures\FakeBookingModel;
use PHPUnit\Framework\TestCase;

class BookingModelTest extends TestCase
{
    public function testEncodesTokensLikeContaoEncodesFormInput(): void
    {
        $booking = new FakeBookingModel([
            'id' => 1,
            'email' => 'visitor@example.org',
            'data' => \serialize([
                'name' => 'x" onmouseover="alert(1)',
                'note' => '<b>{{date}}</b>',
                'options' => ['a\'b', 'c'],
            ]),
        ]);

        $tokens = $booking->collectTokens();

        $this->assertSame('x&#34; onmouseover&#61;&#34;alert&#40;1&#41;', $tokens['data_name']);
        $this->assertSame('&#60;b&#62;&#123;&#123;date&#125;&#125;&#60;/b&#62;', $tokens['data_note']);
        $this->assertSame(['a&#39;b', 'c'], $tokens['data_options']);
        $this->assertSame('visitor@example.org', $tokens['email']);
        $this->assertSame(1, $tokens['booking_id']);
    }

    public function testKeepsTheRecipientAddressAsItIs(): void
    {
        // Valid, but encoding would turn ' into &#39;, which the Notification Center drops as a recipient
        $booking = new FakeBookingModel(['id' => 1, 'email' => "o'brien+a=b#c@example.org", 'data' => \serialize([])]);

        $tokens = $booking->collectTokens();

        $this->assertSame("o'brien+a=b#c@example.org", $tokens['email']);
        $this->assertSame("o'brien+a=b#c@example.org", $tokens['booking_email']);
    }
}
