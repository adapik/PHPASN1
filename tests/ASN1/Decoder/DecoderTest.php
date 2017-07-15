<?php

namespace FG\Test\ASN1\Universal;

use FG\ASN1\Decoder\Decoder;
use FG\ASN1\ExplicitlyTaggedObject;
use FG\ASN1\Universal\GeneralizedTime;
use FG\Test\ASN1TestCase;
use FG\ASN1\UnknownConstructedObject;
use FG\ASN1\UnknownObject;
use FG\ASN1\Identifier;
use FG\ASN1\Universal\BitString;
use FG\ASN1\Universal\Boolean;
use FG\ASN1\Universal\Enumerated;
use FG\ASN1\Universal\Integer;
use FG\ASN1\Universal\NullObject;
use FG\ASN1\Universal\ObjectIdentifier;
use FG\ASN1\Universal\OctetString;
use FG\ASN1\Universal\Sequence;
use FG\ASN1\Universal\IA5String;
use FG\ASN1\Universal\PrintableString;

class DecoderTest extends ASN1TestCase
{
    /**
     * @var Decoder
     */
    private $decoder;
    
    public function setUp()
    {
        $this->decoder = new Decoder();
    }
    
    /**
     * For the real parsing tests look in the test cases of each single ASn object.
     */
    public function testFromBinary()
    {
        /* @var BitString $parsedObject */
        $binaryData  = chr(Identifier::BITSTRING);
        $binaryData .= chr(0x03);
        $binaryData .= chr(0x00);
        $binaryData .= chr(0xFF);
        $binaryData .= chr(0xA0);

        $expectedObject = BitString::createFromHexString('FFA0');
        $parsedObject   = $this->decoder->fromBinary($binaryData);
        $this->assertTrue($parsedObject instanceof BitString);
        $this->assertEquals($expectedObject, $parsedObject);
        $this->assertEquals($expectedObject->getNumberOfUnusedBits(), $parsedObject->getNumberOfUnusedBits());

        /* @var OctetString $parsedObject */
        $binaryData  = chr(Identifier::OCTETSTRING);
        $binaryData .= chr(0x02);
        $binaryData .= chr(0xFF);
        $binaryData .= chr(0xA0);

        $expectedObject = OctetString::createFromString(hex2bin('FFA0'));
        $parsedObject   = $this->decoder->fromBinary($binaryData);
        $this->assertTrue($parsedObject instanceof OctetString);
        $this->assertEquals($expectedObject, $parsedObject);

        /* @var \FG\ASN1\Universal\Boolean $parsedObject */
        $binaryData  = chr(Identifier::BOOLEAN);
        $binaryData .= chr(0x01);
        $binaryData .= chr(0xFF);

        $expectedObject = Boolean::create(true);
        $parsedObject   = $this->decoder->fromBinary($binaryData);
        $this->assertTrue($parsedObject instanceof Boolean);
        $this->assertEquals($expectedObject, $parsedObject);

        /* @var Enumerated $parsedObject */
        $binaryData  = chr(Identifier::ENUMERATED);
        $binaryData .= chr(0x01);
        $binaryData .= chr(0x03);

        $expectedObject = Enumerated::create(3);
        $parsedObject   = $this->decoder->fromBinary($binaryData);
        $this->assertTrue($parsedObject instanceof Enumerated);
        $this->assertEquals($expectedObject, $parsedObject);

        /* @var IA5String $parsedObject */
        $string      = 'Hello Foo World!!!11EinsEins!1';
        $binaryData  = chr(Identifier::IA5_STRING);
        $binaryData .= chr(strlen($string));
        $binaryData .= $string;

        $expectedObject = IA5String::createFromString($string);
        $parsedObject   = $this->decoder->fromBinary($binaryData);
        $this->assertTrue($parsedObject instanceof IA5String);
        $this->assertEquals($expectedObject, $parsedObject);

        /* @var \FG\ASN1\Universal\Integer $parsedObject */
        $binaryData  = chr(Identifier::INTEGER);
        $binaryData .= chr(0x01);
        $binaryData .= chr(123);

        $expectedObject = Integer::create(123);
        $parsedObject   = $this->decoder->fromBinary($binaryData);
        $this->assertTrue($parsedObject instanceof Integer);
        $this->assertEquals($expectedObject, $parsedObject);

        /* @var \FG\ASN1\Universal\NullObject $parsedObject */
        $binaryData  = chr(Identifier::NULL);
        $binaryData .= chr(0x00);

        $expectedObject = NullObject::create();
        $parsedObject   = $this->decoder->fromBinary($binaryData);
        $this->assertTrue($parsedObject instanceof NullObject);
        $this->assertEquals($expectedObject, $parsedObject);

        /* @var ObjectIdentifier $parsedObject */
        $binaryData  = chr(Identifier::OBJECT_IDENTIFIER);
        $binaryData .= chr(0x02);
        $binaryData .= chr(1 * 40 + 2);
        $binaryData .= chr(3);

        $expectedObject = ObjectIdentifier::create('1.2.3');
        $parsedObject   = $this->decoder->fromBinary($binaryData);
        $this->assertTrue($parsedObject instanceof ObjectIdentifier);
        $this->assertEquals($expectedObject->getContent(), $parsedObject->getContent());

        /* @var PrintableString $parsedObject */
        $string      = 'This is a test string. ?()+,/';
        $binaryData  = chr(Identifier::PRINTABLE_STRING);
        $binaryData .= chr(strlen($string));
        $binaryData .= $string;

        $expectedObject = PrintableString::createFromString($string);
        $parsedObject   = $this->decoder->fromBinary($binaryData);
        $this->assertTrue($parsedObject instanceof PrintableString);
        $this->assertEquals($expectedObject, $parsedObject);

        /* @var GeneralizedTime $parsedObject */
        $binaryData  = chr(Identifier::GENERALIZED_TIME);
        $binaryData .= chr(15);
        $binaryData .= '20120923202316Z';

        $expectedObject = GeneralizedTime::createFormDateTime(new \DateTime('2012-09-23 20:23:16'));
        $parsedObject   = $this->decoder->fromBinary($binaryData);
        $this->assertTrue($parsedObject instanceof GeneralizedTime);
        $this->assertEquals($expectedObject->getContent(), $parsedObject->getContent());

        /* @var Sequence $parsedObject */
        $binaryData  = chr(Identifier::IS_CONSTRUCTED | Identifier::SEQUENCE);
        $binaryData .= chr(0x06);
        $binaryData .= chr(Identifier::BOOLEAN);
        $binaryData .= chr(0x01);
        $binaryData .= chr(0x00);
        $binaryData .= chr(Identifier::INTEGER);
        $binaryData .= chr(0x01);
        $binaryData .= chr(0x03);

        $parsedObject = $this->decoder->fromBinary($binaryData);
        $this->assertTrue($parsedObject instanceof Sequence);
        $this->assertCount(2, $parsedObject->getChildren());

        /* @var ExplicitlyTaggedObject $parsedObject */
        $taggedObject = ExplicitlyTaggedObject::create(0x01, PrintableString::createFromString('Hello tagged world'));
        $binaryData   = $taggedObject->getBinary();
        $parsedObject = $this->decoder->fromBinary($binaryData);
        $this->assertTrue($parsedObject instanceof ExplicitlyTaggedObject);

        // An unknown constructed object containing 2 integer children,
        // first 3 bytes are the identifier.
        $binaryData   = "\x3F\x81\x7F\x06".chr(Identifier::INTEGER)."\x01\x42".chr(Identifier::INTEGER)."\x01\x69";
        $offsetIndex  = 0;
        $parsedObject = $this->decoder->fromBinary($binaryData, $offsetIndex);
        $this->assertTrue($parsedObject instanceof UnknownConstructedObject);
        $this->assertEquals(substr($binaryData, 0, 3), $parsedObject->getIdentifier()->getBinary());
        $this->assertCount(2, $parsedObject->getChildren());
        $this->assertEquals(strlen($binaryData), $offsetIndex);
        $this->assertEquals(10, $parsedObject->getObjectLength());

        // First 3 bytes are the identifier
        $binaryData   = "\x1F\x81\x7F\x01\xFF";
        $offsetIndex  = 0;
        $parsedObject = $this->decoder->fromBinary($binaryData, $offsetIndex);
        $this->assertTrue($parsedObject instanceof UnknownObject);
        $this->assertEquals(substr($binaryData, 0, 3), $parsedObject->getIdentifier()->getBinary());
        $this->assertEquals(strlen($binaryData), $offsetIndex);
        $this->assertEquals(5, $parsedObject->getObjectLength());
    }
}