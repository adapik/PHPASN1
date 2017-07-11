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

use DateTime;
use FG\Test\ASN1TestCase;
use FG\ASN1\Identifier;
use FG\ASN1\Universal\GeneralizedTime;

class GeneralizedTimeTest extends ASN1TestCase
{
    private $UTC;

    public function setUp()
    {
        $this->UTC = new \DateTimeZone('UTC');
    }

    public function testGetIdentifier()
    {
        $object = GeneralizedTime::createFormDateTime(new DateTime());
        $this->assertEquals(Identifier::GENERALIZED_TIME, $object->getIdentifier()->getTagNumber());
    }

    public function testGetStringValue()
    {
        $now = new DateTime();
        $now->setTimezone($this->UTC);
        $object = GeneralizedTime::createFormDateTime($now);
        $value  = (string) $object;
        $this->assertEquals($now->format(DATE_RFC3339), $value);

        $timeString = '2012-09-23 20:27';
        $dateTime   = new DateTime($timeString, $this->UTC);
        $object     = GeneralizedTime::createFormDateTime($dateTime);
        $value      = (string) $object;
        $this->assertEquals($dateTime->format(DATE_RFC3339), $value);
    }

    public function testGetObjectLength()
    {
        $object       = GeneralizedTime::createFormDateTime(new DateTime());
        $expectedSize = 2 + 15; // Identifier + length + YYYYMMDDHHmmSSZ
        $this->assertEquals($expectedSize, $object->getObjectLength());

        // without specified daytime
        $object = GeneralizedTime::createFormDateTime(new DateTime('2012-09-23'));
        $this->assertEquals($expectedSize, $object->getObjectLength());

        // with fractional-seconds elements
        $object = GeneralizedTime::createFormDateTime(new DateTime('2012-09-23 22:21:03.5435440'));
        $this->assertEquals($expectedSize + 7, $object->getObjectLength());
    }

    public function testGetBinary()
    {
        $expectedType   = chr(Identifier::GENERALIZED_TIME);
        $expectedLength = chr(15); // YYYYMMDDHHmmSSZ

        $now    = new DateTime();
        $now->setTimezone($this->UTC);
        $object = GeneralizedTime::createFormDateTime($now);
        $expectedContent = $now->format('YmdHis').'Z';
        $this->assertSame($expectedType.$expectedLength.$expectedContent, $object->getBinary());

        $dateString = '2012-09-23';
        $object = GeneralizedTime::createFormDateTime(new DateTime($dateString));
        $expectedContent = '20120923000000Z';
        $this->assertEquals($expectedType.$expectedLength.$expectedContent, $object->getBinary());

        $dateString = '1987-01-15 12:12';
        $object = GeneralizedTime::createFormDateTime(new DateTime($dateString));
        $expectedContent  = '19870115121200Z';
        $this->assertEquals($expectedType.$expectedLength.$expectedContent, $object->getBinary());

        $dateString = '2008-07-01 22:35:17.02';
        $expectedLength = chr(18);
        $object = GeneralizedTime::createFormDateTime(new DateTime($dateString));
        $expectedContent  = '20080701223517.02Z';
        $this->assertEquals($expectedType.$expectedLength.$expectedContent, $object->getBinary());

        $dateString = '2008-07-01 22:35:17.024540';
        $expectedLength = chr(21);
        $object = GeneralizedTime::createFormDateTime(new DateTime($dateString));
        $expectedContent  = '20080701223517.02454Z';
        $this->assertEquals($expectedType.$expectedLength.$expectedContent, $object->getBinary());
    }

    /**
     * @depends testGetBinary
     */
    public function testFromBinaryWithDEREncoding()
    {
        $dateTime = new DateTime('2012-09-23 20:23:16', $this->UTC);
        $binaryData  = chr(Identifier::GENERALIZED_TIME);
        $binaryData .= chr(15);
        $binaryData .= '20120923202316Z';
        $parsedObject = GeneralizedTime::fromBinary($binaryData);
        $this->assertEquals($dateTime->format(DATE_RFC3339), (string) $parsedObject);
    }

    /**
     * @depends testGetBinary
     */
    public function testFromBinaryWithDEREncodingAndFractionalSecondsPart()
    {
        $dateTime = new DateTime('2012-09-23 22:21:03.5435440', $this->UTC);
        $binaryData  = chr(Identifier::GENERALIZED_TIME);
        $binaryData .= chr(22);
        $binaryData .= '20120923222103.543544Z';
        $parsedObject = GeneralizedTime::fromBinary($binaryData);
        $this->assertEquals($dateTime->format('Y-m-d\TH:i:s.uP'), (string) $parsedObject);
    }

    /**
     *
     */
    public function testFromBinaryWithBEREncodingWithLocalTimeZone()
    {
        $dateTime = new DateTime('2012-09-23 20:23:16');
        $binaryData  = chr(Identifier::GENERALIZED_TIME);
        $binaryData .= chr(14);
        $binaryData .= '20120923202316';
        $parsedObject = GeneralizedTime::fromBinary($binaryData);
        $this->assertEquals($dateTime->format('Y-m-d\TH:i:sP'), (string) $parsedObject);
    }

    /**
     * @depends testGetBinary
     */
    public function testFromBinaryWithBEREncodingWithOtherTimeZone()
    {
        $dateTime = new DateTime('2012-09-23 22:13:59', $this->UTC);
        $binaryData  = chr(Identifier::GENERALIZED_TIME);
        $binaryData .= chr(19);
        $binaryData .= '20120923161359-0600';
        $parsedObject = GeneralizedTime::fromBinary($binaryData);
        $this->assertEquals($dateTime->format('Y-m-d\TH:i:sP'), (string) $parsedObject);

        $dateTime = new DateTime('2012-09-23 22:13:59', $this->UTC);
        $binaryData  = chr(Identifier::GENERALIZED_TIME);
        $binaryData .= chr(19);
        $binaryData .= '20120924021359+0400';
        $parsedObject = GeneralizedTime::fromBinary($binaryData);
        $this->assertEquals($dateTime->format('Y-m-d\TH:i:sP'), (string) $parsedObject);
    }

    /**
     * @depends testFromBinaryWithDEREncodingAndFractionalSecondsPart
     */
    public function testFromBinaryWithBEREncodingWithFractionalSecondsPartAndOtherTimeZone()
    {
        $dateTime = new DateTime('2012-09-23 22:13:59.525', $this->UTC);
        $binaryData  = chr(Identifier::GENERALIZED_TIME);
        $binaryData .= chr(23);
        $binaryData .= '20120923161359.525-0600';
        $parsedObject = GeneralizedTime::fromBinary($binaryData);
        $this->assertEquals($dateTime->format('Y-m-d\TH:i:s.uP'), (string) $parsedObject);
    }
}
