<?php
/*
 * This file is part of the PHPASN1 library.
 *
 * Copyright © Friedrich Große <friedrich.grosse@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FG\ASN1;

use DateInterval;
use DateTime;
use DateTimeZone;
use Exception;

abstract class AbstractTime extends Object
{
    /** @var DateTime */
    protected $value;

    public function __construct(Identifier $identifier, ContentLength $contentLength, Content $content, array $children = [])
    {

        parent::__construct($identifier, $contentLength, $content,$children);

        if(!$this->identifier->isConstructed) {
            $this->setValue($content);
        }
    }

    abstract public function setValue(Content $content);

    /**
     * @param string $dateTime Format YYYYMMDDHHmmss.mcsZ
     *
     * @return string
     */
    public function encodeValue(string $dateTime)
    {
        $dateTime = rtrim($dateTime, '0Z.');

        return $dateTime;
    }

    public function getStringValue()
    {
        return $this->value->format("Y-m-d\tH:i:s");
    }

    protected function getLastDateTimeErrors()
    {
        $messages = '';
        $lastErrors = DateTime::getLastErrors();
        foreach ($lastErrors['errors'] as $errorMessage) {
            $messages .= "{$errorMessage}, ";
        }

        return substr($messages, 0, -2);
    }

    public function __toString()
    {
        return $this->value->format("Y-m-d\tH:i:s");
    }

    protected static function extractTimeZoneData(&$binaryData, &$offsetIndex, DateTime $dateTime)
    {
        $sign = $binaryData[$offsetIndex++];
        $timeOffsetHours   = intval(substr($binaryData, $offsetIndex, 2));
        $timeOffsetMinutes = intval(substr($binaryData, $offsetIndex + 2, 2));
        $offsetIndex += 4;

        $interval = new DateInterval("PT{$timeOffsetHours}H{$timeOffsetMinutes}M");
        if ($sign == '+') {
            $dateTime->sub($interval);
        } else {
            $dateTime->add($interval);
        }

        return $dateTime;
    }

    abstract public static function createFormDateTime(\DateTimeInterface $dateTime, array $options = []);
}
