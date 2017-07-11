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
use FG\ASN1\Universal\UTCTime;

class UTCTimeTest extends ASN1TestCase
{
    private $UTC;

    public function setUp()
    {
        $this->UTC = new \DateTimeZone('UTC');
    }

    public function testGetIdentifier()
    {
        $object = UTCTime::createFormDateTime();
        $this->assertEquals(Identifier::UTC_TIME, $object->getIdentifier()->getTagNumber());
    }

    public function testGetContent()
    {
        $now = new \DateTime();
        $now->setTimezone($this->UTC);
        $object = UTCTime::createFormDateTime($now);
        $this->assertEquals($now->format(DateTime::ATOM), (string) $object);

        $timeString = '2012-09-23 20:27';
        $dateTime   = new \DateTime($timeString, $this->UTC);
        $object     = UTCTime::createFormDateTime($dateTime);
        $this->assertEquals($dateTime->format(DateTime::ATOM), (string) $object);
    }

    public function testGetObjectLength()
    {
        $object       = UTCTime::createFormDateTime();
        $expectedSize = 2 + 13; // Identifier + length + YYMMDDHHmmssZ
        $this->assertEquals($expectedSize, $object->getObjectLength());

        $object = UTCTime::createFormDateTime(new DateTime('2012-09-23'));
        $this->assertEquals($expectedSize, $object->getObjectLength());

        $object = UTCTime::createFormDateTime(new DateTime('1987-01-15 12:12:16'));
        $this->assertEquals($expectedSize, $object->getObjectLength());
    }

    public function testGetBinary()
    {
        $expectedType = chr(Identifier::UTC_TIME);
        $expectedLength = chr(13);

        $now = new \DateTime();
        $now->setTimezone($this->UTC);
        $object = UTCTime::createFormDateTime($now);
        $expectedContent = $now->format('ymdHis').'Z';
        $this->assertEquals($expectedType.$expectedLength.$expectedContent, $object->getBinary());

        $dateString = '2012-09-23';
        $date = new \DateTime($dateString, $this->UTC);
        $object = UTCTime::createFormDateTime($date);
        $expectedContent  = $date->format('ymdHis').'Z';
        $this->assertEquals($expectedType.$expectedLength.$expectedContent, $object->getBinary());

        $dateString = '1987-01-15 12:12';
        $date = new \DateTime($dateString, $this->UTC);
        $object = UTCTime::createFormDateTime($date);
        $expectedContent  = $date->format('ymdHis').'Z';
        $this->assertEquals($expectedType.$expectedLength.$expectedContent, $object->getBinary());
    }

    /**
     * @depends testGetBinary
     */
    public function testFromBinaryWithDEREncoding()
    {
        $dateTime = new \DateTime('2012-09-23 20:23:16', $this->UTC);
        $binaryData  = chr(Identifier::UTC_TIME);
        $binaryData .= chr(13);
        $binaryData .= '120923202316Z';
        $parsedObject = UTCTime::fromBinary($binaryData);
        $this->assertEquals($dateTime->format(DateTime::ATOM), (string) $parsedObject);
    }

    /**
     * @depends testGetBinary
     */
    public function testFromBinaryWithBEREncodingWithSecondsInOtherTimeZone()
    {
        $dateTime = new \DateTime('2012-09-23 22:13:32', $this->UTC);
        $binaryData  = chr(Identifier::UTC_TIME);
        $binaryData .= chr(15);
        $binaryData .= '120923161332-0600';
        $parsedObject = UTCTime::fromBinary($binaryData);
        $this->assertEquals($dateTime->format(DateTime::ATOM), (string) $parsedObject);

        $dateTime = new \DateTime('2012-09-23 22:13:32', $this->UTC);
        $binaryData  = chr(Identifier::UTC_TIME);
        $binaryData .= chr(15);
        $binaryData .= '120924021332+0400';
        $parsedObject = UTCTime::fromBinary($binaryData);
        $this->assertEquals($dateTime->format(DateTime::ATOM), (string) $parsedObject);
    }

    /**
     * @depends testFromBinaryWithDEREncoding
     * @depends testFromBinaryWithBEREncodingWithSecondsInOtherTimeZone
     */
    public function testFromBinaryWithOffset()
    {
        $binaryData  = chr(Identifier::UTC_TIME);
        $binaryData .= chr(13);
        $binaryData .= '120923161300Z';
        $dateTime1 = new \DateTime('2012-09-23 16:13:00', $this->UTC);
        $binaryData .= chr(Identifier::UTC_TIME);
        $binaryData .= chr(13);
        $binaryData .= '120923180030Z';
        $dateTime2 = new \DateTime('2012-09-23 18:00:30', $this->UTC);
        $binaryData .= chr(Identifier::UTC_TIME);
        $binaryData .= chr(17);
        $binaryData .= '120924021332+0400';
        $dateTime3 = new \DateTime('2012-09-23 22:13:32', $this->UTC);

        $offset = 0;
        $parsedObject = UTCTime::fromBinary($binaryData, $offset);
        $this->assertEquals($dateTime1->format(DateTime::ATOM), (string) $parsedObject);
        $this->assertEquals(15, $offset);
        $parsedObject = UTCTime::fromBinary($binaryData, $offset);
        $this->assertEquals($dateTime2->format(DateTime::ATOM), (string) $parsedObject);
        $this->assertEquals(30, $offset);
        $parsedObject = UTCTime::fromBinary($binaryData, $offset);
        $this->assertEquals($dateTime3->format(DateTime::ATOM), (string) $parsedObject);
        $this->assertEquals(49, $offset);
    }
}
