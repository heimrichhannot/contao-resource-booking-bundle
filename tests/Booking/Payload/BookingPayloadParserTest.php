<?php

namespace HeimrichHannot\ResourceBookingBundle\Tests\Booking\Payload;

use HeimrichHannot\ResourceBookingBundle\Booking\Payload\BookingPayloadParser;
use HeimrichHannot\ResourceBookingBundle\Exception\InvalidBookingPayloadException;
use PHPUnit\Framework\TestCase;

class BookingPayloadParserTest extends TestCase
{
    private const ALLOWED = [1, 2, 3];

    private BookingPayloadParser $parser;
    private \DateTimeZone $timezone;

    protected function setUp(): void
    {
        $this->parser = new BookingPayloadParser();
        $this->timezone = new \DateTimeZone('Europe/Berlin');
    }

    public function testParsesValidPayload(): void
    {
        $payload = $this->parser->parse(
            '{"resources":[{"id":1,"quantity":1},{"id":3,"quantity":1}],"start":"2027-04-05T22:00:00.000Z","end":"2027-04-07T22:00:00.000Z"}',
            self::ALLOWED,
            $this->timezone,
        );

        $this->assertSame([1, 3], $payload->resourceIds);
        $this->assertSame('2027-04-06 00:00:00 Europe/Berlin', $payload->start->format('Y-m-d H:i:s e'));
        $this->assertSame('2027-04-08 00:00:00 Europe/Berlin', $payload->end->format('Y-m-d H:i:s e'));
        $this->assertSame(3, $payload->getDurationDays());
    }

    public function testNormalizesInstantsToTheServerDay(): void
    {
        // Midnight UTC (e.g. a browser in London) belongs to the same day in Berlin
        $payload = $this->parser->parse(
            '{"resources":[{"id":1,"quantity":1}],"start":"2027-04-06T00:00:00.000Z","end":"2027-04-06T00:00:00.000Z"}',
            self::ALLOWED,
            $this->timezone,
        );

        $this->assertSame('2027-04-06 00:00', $payload->start->format('Y-m-d H:i'));
        $this->assertSame(1, $payload->getDurationDays());
    }

    public function testDecodesHtmlEntitiesAddedByContao(): void
    {
        $payload = $this->parser->parse(
            '{&quot;resources&quot;:[{&quot;id&quot;:2,&quot;quantity&quot;:1}],&quot;start&quot;:&quot;2027-04-05T22:00:00.000Z&quot;,&quot;end&quot;:&quot;2027-04-05T22:00:00.000Z&quot;}',
            self::ALLOWED,
            $this->timezone,
        );

        $this->assertSame([2], $payload->resourceIds);
    }

    /**
     * @dataProvider invalidPayloads
     */
    public function testRejectsInvalidPayload(string $raw): void
    {
        $this->expectException(InvalidBookingPayloadException::class);

        $this->parser->parse($raw, self::ALLOWED, $this->timezone);
    }

    public static function invalidPayloads(): iterable
    {
        $valid = ['resources' => [['id' => 1, 'quantity' => 1]], 'start' => '2027-04-05T22:00:00.000Z', 'end' => '2027-04-07T22:00:00.000Z'];
        $with = static fn (array $changes): string => \json_encode(\array_replace($valid, $changes));

        yield 'pentest payload (two-digit year)' => ['{"resources":[{"id":1,"quantity":999999999999}],"start":"21-09-25T22:00:00.000Z","end":"21-09-28T22:00:00.000Z"}'];
        yield 'two-digit year' => [$with(['start' => '21-09-25T22:00:00.000Z'])];
        yield 'five-digit year' => [$with(['end' => '10000-01-01T00:00:00.000Z'])];
        yield 'non-existent day' => [$with(['start' => '2027-02-31T22:00:00.000Z'])];
        yield 'hour 24' => [$with(['start' => '2027-04-05T24:00:00.000Z'])];
        yield 'without milliseconds' => [$with(['start' => '2027-04-05T22:00:00Z'])];
        yield 'offset instead of Z' => [$with(['start' => '2027-04-05T22:00:00.000+00:00'])];
        yield 'timestamp instead of string' => [$with(['start' => 1806962400])];
        yield 'end before start' => [$with(['start' => '2027-04-07T22:00:00.000Z', 'end' => '2027-04-05T22:00:00.000Z'])];
        yield 'quantity 999999999999' => [$with(['resources' => [['id' => 1, 'quantity' => 999999999999]]])];
        yield 'quantity 0' => [$with(['resources' => [['id' => 1, 'quantity' => 0]]])];
        yield 'quantity as string' => [$with(['resources' => [['id' => 1, 'quantity' => '1']]])];
        yield 'id as string' => [$with(['resources' => [['id' => '1', 'quantity' => 1]]])];
        yield 'id as float' => ['{"resources":[{"id":1.0,"quantity":1}],"start":"2027-04-05T22:00:00.000Z","end":"2027-04-07T22:00:00.000Z"}'];
        yield 'negative id' => [$with(['resources' => [['id' => -1, 'quantity' => 1]]])];
        yield 'resource not allowed' => [$with(['resources' => [['id' => 999, 'quantity' => 1]]])];
        yield 'one of several not allowed' => [$with(['resources' => [['id' => 1, 'quantity' => 1], ['id' => 999, 'quantity' => 1]]])];
        yield 'duplicate resource' => [$with(['resources' => [['id' => 1, 'quantity' => 1], ['id' => 1, 'quantity' => 1]]])];
        yield 'resource not an object' => [$with(['resources' => [1]])];
        yield 'resource with extra key' => [$with(['resources' => [['id' => 1, 'quantity' => 1, 'x' => 1]]])];
        yield 'resources empty' => [$with(['resources' => []])];
        yield 'resources as object' => [$with(['resources' => ['a' => ['id' => 1, 'quantity' => 1]]])];
        yield 'extra top-level key' => [$with(['demo' => 'example'])];
        yield 'missing end' => [\json_encode(['resources' => [['id' => 1, 'quantity' => 1]], 'start' => '2027-04-05T22:00:00.000Z'])];
        yield 'initial form value' => ['{"demo":"example"}'];
        yield 'list instead of object' => ['[1,2,3]'];
        yield 'too deep' => [$with(['resources' => [['id' => [[1]], 'quantity' => 1]]])];
        yield 'too large' => [\str_repeat(' ', BookingPayloadParser::MAX_PAYLOAD_BYTES) . $with([])];
        yield 'invalid JSON' => ['{"resources":'];
        yield 'empty' => [''];
    }
}
