<?php

namespace FG\Test\ASN1\Decoder;

use FG\ASN1\ASN1Object;
use FG\ASN1\Exception\ParserException;
use FG\ASN1\Mapper\Mapper;
use FG\ASN1\Identifier;
use PHPUnit\Framework\TestCase;

/**
 * Inputs found by fuzzing: must raise ParserException, never warnings, Errors or crashes.
 */
class MalformedInputTest extends TestCase
{
    public static function malformedProvider(): array
    {
        return [
            'integer huge declared length (gmp_pow crash)' => ['02864801650304038f'],
            'integer declared length exceeds input'        => ['30340230'],
            'integer empty'                                => ['0200'],
            'length overflows int'                         => ['30340201008e0b0609608648015b92fee424cc999f889c819a8a68c0258957e3b9f17c1b39d138383838383838383838383838383838'],
            'bit string empty'                             => ['0300'],
            'oid truncated'                                => ['30820a32300b06496086480165fa465e308212560af53204'],
            'oid empty'                                    => ['0600'],
            'utctime short'                                => ['30444000200017805853154810247300666086480165fc030320042280202d32'],
            'utctime NUL bytes'                            => ['17171717178648011b000000000000009595959595959595959547'],
            'generalizedtime NUL bytes'                    => ['3834020100300b0609608648010365042103382280203636805c203636000000363636363636713636363636300b3636363636363636'],
            'generalizedtime NUL in string'                => ['180f3230313230393233000000000000005a'],
            'utctime short universal'                      => ['170401020304'],
            'generalizedtime short'                        => ['3034650318048286481d65030403200422802080202d32'],
            'generalizedtime short universal'              => ['180401020304'],
            'utctime seconds without terminator'           => ['170c' . '313230393233313631333332'],
            'utctime non-digit'                            => ['170b' . '31323039323331364133' . '5a'],
            'utctime bad trailing character'               => ['170d' . '313230393233313631333332' . '58'],
            'generalizedtime non-digit'                    => ['180e' . '3230313230393233313631333358'],
            'generalizedtime malformed fraction'           => ['1810' . '32303132303932333136313333322e5a'],
            'generalizedtime too many fraction digits'     => ['1817' . bin2hex('20120923161332.1234567Z')],
            'time with nested constructed segment'         => ['37022400'],
            'child exceeds parent'                         => ['3002020100'],
        ];
    }

    /**
     * @dataProvider malformedProvider
     */
    public function testThrowsParserException(string $hex): void
    {
        set_error_handler(function ($severity, $message, $file, $line) {
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });

        try {
            $data = hex2bin($hex);
            $this->expectException(ParserException::class);
            ASN1Object::fromBinary($data);
        } finally {
            restore_error_handler();
        }
    }

    public function testConstructedTimeSegmentsAreJoined(): void
    {
        // BER: constructed UTCTime made of two OCTET STRING segments
        $data = "\x37" . \chr(2 + 6 + 2 + 7) . "\x04\x06" . '120923' . "\x04\x07" . '161332Z';
        $object = ASN1Object::fromBinary($data);
        $this->assertSame('2012-09-23T16:13:32+00:00', (string) $object);
    }

    public function testEmptyConstructedBitStringIsAccepted(): void
    {
        $data = hex2bin('2300');
        $this->assertSame(0, ASN1Object::fromBinary($data)->getNumberOfUnusedBits());
    }

    public function testMapperWithMoreChildrenThanMappingReturnsNull(): void
    {
        $data   = hex2bin('3006020100020101');
        $object = ASN1Object::fromBinary($data);
        $mapping = [
            'type'     => Identifier::SEQUENCE,
            'children' => ['a' => ['type' => Identifier::INTEGER]],
        ];

        $this->assertNull((new Mapper())->map($object, $mapping));
    }
}
