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
use FG\ASN1\ElementBuilder;
use FG\ASN1\Identifier;
use FG\ASN1\Exception\ParserException;
use DateTime;
use DateTimeZone;
use FG\ASN1\ContentLength;

/**
 * This ASN.1 universal type contains date and time information according to ISO 8601.
 * The type consists of values representing:
 * a) a calendar date, as defined in ISO 8601; and
 * b) a time of day, to any of the precisions defined in ISO 8601,
 *    except for the hours value 24 which shall not be used; and
 * c) the local time differential factor as defined in ISO 8601.
 * Decoding of this type will accept the Basic Encoding Rules (BER)
 * The encoding will comply with the Distinguished Encoding Rules (DER).
 */
class GeneralizedTime extends AbstractTime
{
    private $microseconds;

    public function __construct(
        Identifier $identifier,
        ContentLength $contentLength,
        Content $content,
        array $children = []
    ) {
        parent::__construct($identifier, $contentLength, $content, $children);

        if (!$this->identifier->isConstructed()) {
            $this->setValue($content);
        }

        $this->microseconds = $this->value->format('u');
        if ($this->containsFractionalSecondsElement()) {
            // DER requires us to remove trailing zeros
            $this->microseconds = preg_replace('/([1-9]+)0+$/', '$1', $this->microseconds);
        }
    }

    public static function getType()
    {
        return Identifier::GENERALIZED_TIME;
    }

    public function containsFractionalSecondsElement()
    {
        return (int)$this->microseconds > 0;
    }

    public function __toString(): string
    {
        if ($this->containsFractionalSecondsElement()) {
            return $this->value->format("Y-m-d\TH:i:s.uP");
        } else {
            return $this->value->format("Y-m-d\TH:i:sP");
        }
    }

    public function setValue(Content $content)
    {
        $binaryData  = $content->getBinary();
        $offsetIndex = 0;

        $lengthOfMinimumTimeString = 14; // YYYYMMDDHHmmSS
        $contentLength             = $this->contentLength->getLength();
        $maximumBytesToRead        = $contentLength;

        $format             = 'YmdGis';
        $contentOctets      = substr($binaryData, $offsetIndex, $contentLength);
        $dateTimeString     = substr($contentOctets, 0, $lengthOfMinimumTimeString);
        $offsetIndex        += $lengthOfMinimumTimeString;
        $maximumBytesToRead -= $lengthOfMinimumTimeString;

        if ($contentLength === $lengthOfMinimumTimeString) {
            $localTimeZone = new \DateTimeZone(date_default_timezone_get());
            $dateTime      = \DateTime::createFromFormat($format, $dateTimeString, $localTimeZone);
            $this->value   = $dateTime;
        } else {
            if ($binaryData[$offsetIndex] === '.') {
                $maximumBytesToRead--; // account for the '.'
                $nrOfFractionalSecondElements = 1; // account for the '.'

                while ($maximumBytesToRead > 0
                    && $binaryData[$offsetIndex + $nrOfFractionalSecondElements] !== '+'
                    && $binaryData[$offsetIndex + $nrOfFractionalSecondElements] !== '-'
                    && $binaryData[$offsetIndex + $nrOfFractionalSecondElements] !== 'Z') {
                    $nrOfFractionalSecondElements++;
                    $maximumBytesToRead--;
                }

                $dateTimeString .= substr($binaryData, $offsetIndex, $nrOfFractionalSecondElements);
                $offsetIndex    += $nrOfFractionalSecondElements;
                $format         .= '.u';
            }

            $dateTime = \DateTime::createFromFormat($format, $dateTimeString, new \DateTimeZone('UTC'));

            if ($maximumBytesToRead > 0) {
                if ($binaryData[$offsetIndex] === '+'
                    || $binaryData[$offsetIndex] === '-'
                ) {
                    $dateTime = static::extractTimeZoneData($binaryData, $offsetIndex, $dateTime);
                } elseif ($binaryData[$offsetIndex++] !== 'Z') {
                    throw new ParserException('Invalid ISO 8601 Time String', $offsetIndex);
                }
            }

            $this->value = $dateTime;

            $this->microseconds = $this->value->format('u');
            if ($this->containsFractionalSecondsElement()) {
                // DER requires us to remove trailing zeros
                $this->microseconds = preg_replace('/([1-9]+)0+$/', '$1', $this->microseconds);
            }
        }
    }

    /**
     * @param string $dateTime Format YYYYMMDDHHmmss.mcsZ
     *
     * @return string
     */
    public static function encodeValue(string $dateTime)
    {
        $hasTimeZone = true;

        if (is_numeric(substr($dateTime, -1, 1))) {
            $hasTimeZone = false;
        }

        $trimString = str_pad(rtrim($dateTime, '0Z.'), 14, '0');
        $dateTime   = $trimString . ($hasTimeZone ? 'Z' : '');

        return $dateTime;
    }

    public static function createFormDateTime(\DateTimeInterface $dateTime = null, array $options = [])
    {
        $dateTime = $dateTime ?? new DateTime('now', new DateTimeZone('UTC'));

        $isConstructed = false;
        $lengthForm    = $options['lengthForm'] ?? ContentLength::SHORT_FORM;

        $string = $dateTime->format('YmdHis.u') . 'Z';

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
