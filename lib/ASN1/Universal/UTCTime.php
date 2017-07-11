<?php
/*
 * This file is part of the PHPASN1 library.
 *
 * Copyright © Friedrich Große <friedrich.grosse@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FG\ASN1\Universal;

use FG\ASN1\AbstractTime;
use FG\ASN1\Content;
use FG\ASN1\ContentLength;
use FG\ASN1\ElementBuilder;
use FG\ASN1\IdentifierManager;
use FG\ASN1\Parsable;
use FG\ASN1\Identifier;
use FG\ASN1\Exception\ParserException;
use DateTime;
use DateTimeZone;
use Exception;

/**
 * This ASN.1 universal type contains the calendar date and time.
 *
 * The precision is one minute or one second and optionally a
 * local time differential from coordinated universal time.
 *
 * Decoding of this type will accept the Basic Encoding Rules (BER)
 * The encoding will comply with the Distinguished Encoding Rules (DER).
 */
class UTCTime extends AbstractTime implements Parsable
{
    public static function getType()
    {
        return Identifier::UTC_TIME;
    }

    protected function calculateContentLength()
    {
        return 13; // Content is a string o the following format: YYMMDDhhmmssZ (13 octets)
    }

    public function getStringValue()
    {
        return $this->value->format('ymdHis').'Z';
    }

    protected function getEncodedValue()
    {
        return $this->value->format('ymdHis').'Z';
    }

    /**
     * @param string $dateTime Format YYYYMMDDHHmmss.mcsZ
     *
     * @return string
     */
    public static function encodeValue(string $dateTime)
    {
        $hasTimeZone = true;

        if(is_numeric(substr($dateTime, -1, 1))) {
            $hasTimeZone = false;
        }

        $trimString = str_pad(rtrim($dateTime, '0Z.'), 12, '0');
        $dateTime = $trimString . ($hasTimeZone ? 'Z' : '');

        return $dateTime;
    }

    public function setValue(Content $content)
    {
        $binaryData = $content->binaryData;
        $offsetIndex = 0;

        $format = 'ymdGi';
        $dateTimeString = substr($binaryData, $offsetIndex, 10);
        $offsetIndex += 10;

        // extract optional seconds part
        if ($binaryData[$offsetIndex] != 'Z'
            && $binaryData[$offsetIndex] != '+'
            && $binaryData[$offsetIndex] != '-') {
            $dateTimeString .= substr($binaryData, $offsetIndex, 2);
            $offsetIndex += 2;
            $format .= 's';
        }

        $dateTime = \DateTime::createFromFormat($format, $dateTimeString, new \DateTimeZone('UTC'));

        // extract time zone settings
        if ($binaryData[$offsetIndex] == '+'
            || $binaryData[$offsetIndex] == '-') {
            $dateTime = static::extractTimeZoneData($binaryData, $offsetIndex, $dateTime);
        } elseif ($binaryData[$offsetIndex] !== 'Z') {
            throw new ParserException('Invalid UTC String', $offsetIndex);
        }

        $dateTimeZone = 'UTC';

        if ($dateTime == null || is_string($dateTime)) {
            $timeZone = new DateTimeZone($dateTimeZone);
            $dateTimeObject = new DateTime($dateTime, $timeZone);
            if ($dateTimeObject == false) {
                $errorMessage = $this->getLastDateTimeErrors();
                $className = IdentifierManager::getName($this->getType());
                throw new Exception(sprintf("Could not create %s from date time string '%s': %s", $className, $dateTime, $errorMessage));
            }
            $dateTime = $dateTimeObject;
        } elseif (!$dateTime instanceof DateTime) {
            throw new Exception('Invalid first argument for some instance of ASN_AbstractTime constructor');
        }

        $this->value = $dateTime;
    }

    public static function createFormDateTime(\DateTimeInterface $dateTime = null, array $options = []) {
        $dateTime = $dateTime ?? new DateTime('now', new DateTimeZone('UTC'));

        $isConstructed = false;
        $lengthForm    = $options['lengthForm'] ?? ContentLength::SHORT_FORM;

        $string = $dateTime->format('ymdHis').'Z';

        return
            ElementBuilder::createObject(
                Identifier::CLASS_UNIVERSAL,
                static::getType(),
                $isConstructed,
                $string,
                $lengthForm
            );
    }
}
