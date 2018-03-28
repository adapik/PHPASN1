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

abstract class AbstractTime extends ASN1Object
{
    /** @var DateTime */
    protected $value;

    public function __construct(
        Identifier $identifier,
        ContentLength $contentLength,
        Content $content,
        array $children = []
    ) {
        parent::__construct($identifier, $contentLength, $content, $children);
    }

    abstract public function setValue(Content $content);

    abstract public static function getType();

    /**
     * @param string $dateTime Format YYYYMMDDHHmmss.mcsZ
     *
     * @return string
     */
    abstract public static function encodeValue(string $dateTime);

    public function __toString(): string
    {
        return $this->getValue()->format("Y-m-d\TH:i:sP");
    }

    protected function getValue()
    {
        if ($this->value === null) {
            $this->setValue($this->content);
        }

        return $this->value;
    }

    protected static function extractTimeZoneData(&$binaryData, &$offsetIndex, DateTime $dateTime)
    {
        $sign              = $binaryData[$offsetIndex++];
        $timeOffsetHours   = (int)substr($binaryData, $offsetIndex, 2);
        $timeOffsetMinutes = (int)substr($binaryData, $offsetIndex + 2, 2);
        $offsetIndex       += 4;

        $interval = new DateInterval("PT{$timeOffsetHours}H{$timeOffsetMinutes}M");
        if ($sign === '+') {
            $dateTime->sub($interval);
        } else {
            $dateTime->add($interval);
        }

        return $dateTime;
    }

    abstract public static function createFormDateTime(\DateTimeInterface $dateTime = null, array $options = []);
}
