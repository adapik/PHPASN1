<?php
/*
 * This file is part of the PHPASN1 library.
 *
 * Copyright © Friedrich Große <friedrich.grosse@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FG\Test\ASN1\Universal;

use FG\ASN1\ASN1Object;
use FG\Test\ASN1TestCase;
use FG\ASN1\Identifier;
use FG\ASN1\Universal\Integer;

class IntegerTest extends ASN1TestCase
{
    public function testGetIdentifier()
    {
        $object = Integer::create(123);
        $this->assertEquals(Identifier::INTEGER, $object->getIdentifier()->getTagNumber());
    }

    public function testGetIdentifierCode()
    {
        $object = Integer::create(123);
        $this->assertEquals('Integer', $object->getIdentifier()->getCode());
    }

    /**
     * @expectedException \Exception
     */
    public function testCreateInstanceCanFail()
    {
        Integer::create('a');
    }

    public function testContent()
    {
        $object = Integer::create(1234);
        $this->assertEquals('1234', (string) $object);

        $object = Integer::create(-1234);
        $this->assertEquals('-1234', (string) $object);

        $object = Integer::create(0);
        $this->assertEquals('0', (string) $object);

        // test with maximum integer value
        $object = Integer::create(PHP_INT_MAX);
        $this->assertEquals((string) PHP_INT_MAX, (string) $object);

        // test with minimum integer value by negating the max value
        $object = Integer::create(~PHP_INT_MAX);
        $this->assertEquals((string) ~PHP_INT_MAX, (string) $object);
    }

    public function testGetObjectLength()
    {
        $positiveObj = Integer::create(0);
        $expectedSize = 2 + 1;
        $this->assertEquals($expectedSize, $positiveObj->getObjectLength());

        $positiveObj = Integer::create(127);
        $negativeObj = Integer::create(-127);
        $expectedSize = 2 + 1;
        $this->assertEquals($expectedSize, $positiveObj->getObjectLength());
        $this->assertEquals($expectedSize, $negativeObj->getObjectLength());

        $positiveObj = Integer::create(128);
        $negativeObj = Integer::create(-128);
        $expectedSize = 2 + 2;
        $this->assertEquals($expectedSize, $positiveObj->getObjectLength());
        $this->assertEquals($expectedSize, $negativeObj->getObjectLength());

        $positiveObj = Integer::create(0x7FFF);
        $negativeObj = Integer::create(-0x7FFF);
        $expectedSize = 2 + 2;
        $this->assertEquals($expectedSize, $positiveObj->getObjectLength());
        $this->assertEquals($expectedSize, $negativeObj->getObjectLength());

        $positiveObj = Integer::create(0x8000);
        $negativeObj = Integer::create(-0x8000);
        $expectedSize = 2 + 3;
        $this->assertEquals($expectedSize, $positiveObj->getObjectLength());
        $this->assertEquals($expectedSize, $negativeObj->getObjectLength());

        $positiveObj = Integer::create(0x7FFFFF);
        $negativeObj = Integer::create(-0x7FFFFF);
        $expectedSize = 2 + 3;
        $this->assertEquals($expectedSize, $positiveObj->getObjectLength());
        $this->assertEquals($expectedSize, $negativeObj->getObjectLength());

        $positiveObj = Integer::create(0x800000);
        $negativeObj = Integer::create(-0x800000);
        $expectedSize = 2 + 4;
        $this->assertEquals($expectedSize, $positiveObj->getObjectLength());
        $this->assertEquals($expectedSize, $negativeObj->getObjectLength());

        $positiveObj = Integer::create(0x7FFFFFFF);
        $negativeObj = Integer::create(-0x7FFFFFFF);
        $expectedSize = 2 + 4;
        $this->assertEquals($expectedSize, $positiveObj->getObjectLength());
        $this->assertEquals($expectedSize, $negativeObj->getObjectLength());
    }

    public function testGetBinary()
    {
        $expectedType = chr(Identifier::INTEGER);
        $expectedLength = chr(0x01);

        $object = Integer::create(0);
        $expectedContent = chr(0x00);
        $this->assertEquals($expectedType.$expectedLength.$expectedContent, $object->getBinary());

        $object = Integer::create(127);
        $expectedContent = chr(0x7F);
        $this->assertEquals($expectedType.$expectedLength.$expectedContent, $object->getBinary());

        $object = Integer::create(-127);
        $expectedContent = chr(0x81);
        $this->assertEquals($expectedType.$expectedLength.$expectedContent, $object->getBinary());

        $object = Integer::create(200);
        $expectedLength = chr(0x02);
        $expectedContent = chr(0x00);
        $expectedContent .= chr(0xC8);
        $this->assertEquals($expectedType.$expectedLength.$expectedContent, $object->getBinary());

        $object = Integer::create(-546);
        $expectedLength = chr(0x02);
        $expectedContent = chr(0xFD);
        $expectedContent .= chr(0xDE);
        $this->assertEquals($expectedType.$expectedLength.$expectedContent, $object->getBinary());

        $object = Integer::create(7420);
        $expectedLength   = chr(0x02);
        $expectedContent  = chr(0x1C);
        $expectedContent .= chr(0xFC);
        $this->assertEquals($expectedType.$expectedLength.$expectedContent, $object->getBinary());

        $object = Integer::create(-1891004);
        $expectedLength   = chr(0x03);
        $expectedContent  = chr(0xE3);
        $expectedContent .= chr(0x25);
        $expectedContent .= chr(0x44);
        $this->assertEquals($expectedType.$expectedLength.$expectedContent, $object->getBinary());
    }

    public function testBigIntegerSupport()
    {
        // Positive bigint
        $expectedType     = chr(Identifier::INTEGER);
        $expectedLength   = chr(0x20);
        $expectedContent  = "\x7f\xff\xff\xff\xff\xff\xff\xff";
        $expectedContent .= "\xff\xff\xff\xff\xff\xff\xff\xff";
        $expectedContent .= "\xff\xff\xff\xff\xff\xff\xff\xff";
        $expectedContent .= "\xff\xff\xff\xff\xff\xff\xff\xff";

        $bigint = gmp_strval(gmp_sub(gmp_pow(2, 255), 1));
        $object = Integer::create($bigint);
        $binary = $object->getBinary();
        $this->assertEquals($expectedType.$expectedLength.$expectedContent, $binary);

        $obj = ASN1Object::fromBinary($binary);
        $this->assertEquals($obj, $object);

        // Test a negative number
        $expectedLength   = chr(0x21);
        $expectedContent  = "\x00\x80\x00\x00\x00\x00\x00\x00\x00";
        $expectedContent .= "\x00\x00\x00\x00\x00\x00\x00\x00";
        $expectedContent .= "\x00\x00\x00\x00\x00\x00\x00\x00";
        $expectedContent .= "\x00\x00\x00\x00\x00\x00\x00\x00";
        $bigint = gmp_strval(gmp_pow(2, 255));
        $object = Integer::create($bigint);
        $binary = $object->getBinary();
        $this->assertEquals($expectedType.$expectedLength.$expectedContent, $binary);

        $obj = ASN1Object::fromBinary($binary);
        $this->assertEquals($object, $obj);
    }

    /**
     * @dataProvider bigIntegersProvider
     */
    public function testSerializeBigIntegers($i)
    {
        $object = Integer::create($i);
        $binary = $object->getBinary();

        $obj = ASN1Object::fromBinary($binary);
        $this->assertEquals($obj->getContent(), $object->getContent());
    }

    public function bigIntegersProvider()
    {
        for ($i = 1; $i <= 256; $i *= 2) {
            // 2 ^ n [0, 256]  large positive numbers
            yield [gmp_strval(gmp_pow(2, $i))];
        }

        for ($i = 1; $i <= 256; $i *= 2) {
            // 0 - 2 ^ n [0, 256]  large negative numbers
            yield [gmp_strval(gmp_sub(0, gmp_pow(2, $i)))];
        }
    }

    /**
     * @depends testGetBinary
     */
    public function testFromBinary()
    {
        $originalObject = Integer::create(200);
        $binaryData = $originalObject->getBinary();
        $parsedObject = Integer::fromBinary($binaryData);
        $this->assertEquals($originalObject, $parsedObject);

        $originalObject = Integer::create(12345);
        $binaryData = $originalObject->getBinary();
        $parsedObject = Integer::fromBinary($binaryData);
        $this->assertEquals($originalObject, $parsedObject);

        $originalObject = Integer::create(-1891004);
        $binaryData = $originalObject->getBinary();
        $parsedObject = Integer::fromBinary($binaryData);
        $this->assertEquals($originalObject, $parsedObject);
    }

    /**
     * @depends testFromBinary
     */
    public function testFromBinaryWithOffset()
    {
        $originalObject1 = Integer::create(12345);
        $originalObject2 = Integer::create(67890);

        $binaryData  = $originalObject1->getBinary();
        $binaryData .= $originalObject2->getBinary();

        $offset = 0;
        $parsedObject = Integer::fromBinary($binaryData, $offset);
        $this->assertEquals($originalObject1, $parsedObject);
        $this->assertEquals(4, $offset);
        $parsedObject = Integer::fromBinary($binaryData, $offset);
        $this->assertEquals($originalObject2, $parsedObject);
        $this->assertEquals(9, $offset);
    }

    public function testToGMP()
    {
        $object = Integer::create(123);
        $gmp = $object->toGMP();
        $this->assertInstanceOf(\GMP::class, $gmp);
        $this->assertEquals('123', gmp_strval($gmp));

        $object = Integer::create(-456);
        $gmp = $object->toGMP();
        $this->assertInstanceOf(\GMP::class, $gmp);
        $this->assertEquals('-456', gmp_strval($gmp));

        $object = Integer::create(0);
        $gmp = $object->toGMP();
        $this->assertInstanceOf(\GMP::class, $gmp);
        $this->assertEquals('0', gmp_strval($gmp));

        // Test with big integer
        $bigint = gmp_strval(gmp_pow(2, 128));
        $object = Integer::create($bigint);
        $gmp = $object->toGMP();
        $this->assertInstanceOf(\GMP::class, $gmp);
        $this->assertEquals($bigint, gmp_strval($gmp));
    }

    public function testBackwardCompatibilityValueProperty()
    {
        $object = Integer::create(123);
        $this->assertEquals('123', $object->value);

        $object = Integer::create(-456);
        $this->assertEquals('-456', $object->value);

        $object = Integer::create(0);
        $this->assertEquals('0', $object->value);

        // Test with big integer
        $bigint = gmp_strval(gmp_pow(2, 128));
        $object = Integer::create($bigint);
        $this->assertEquals($bigint, $object->value);
    }
}
