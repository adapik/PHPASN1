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

use Exception;
use FG\ASN1\ElementBuilder;
use FG\ASN1\ASN1Object;
use FG\ASN1\Identifier;
use FG\ASN1\ContentLength;
use FG\ASN1\Content;

class Integer extends ASN1Object
{
    /** @var int */
    public $value;

    /**
     * @param int $value
     *
     * @throws Exception if the value is not numeric
     */
    public function __construct(
        Identifier $identifier,
        ContentLength $contentLength,
        Content $content,
        array $children = []
    ) {
        parent::__construct($identifier, $contentLength, $content, $children);

        $this->setValue($content);
    }

    public static function getType()
    {
        return Identifier::INTEGER;
    }

    protected static function calculateContentLength($value)
    {
        $nrOfOctets = 1; // we need at least one octet
        $tmpValue   = gmp_abs(gmp_init($value, 10));
        while (gmp_cmp($tmpValue, 127) > 0) {
            $tmpValue = self::rightShift($tmpValue, 8);
            $nrOfOctets++;
        }
        return $nrOfOctets;
    }

    /**
     * @param resource|\GMP $number
     * @param int           $positions
     *
     * @return resource|\GMP
     */
    private static function rightShift($number, $positions)
    {
        // Shift 1 right = div / 2
        return gmp_div($number, gmp_pow(2, (int)$positions));
    }

    public static function encodeValue($value): string
    {
        $numericValue  = gmp_init($value, 10);
        $contentLength = self::calculateContentLength($value);

        if (gmp_sign($numericValue) < 0) {
            $numericValue = gmp_add($numericValue, gmp_sub(gmp_pow(2, 8 * $contentLength), 1));
            $numericValue = gmp_add($numericValue, 1);
        }

        $result = '';
        for ($shiftLength = ($contentLength - 1) * 8; $shiftLength >= 0; $shiftLength -= 8) {
            $octet  = gmp_strval(gmp_mod(self::rightShift($numericValue, $shiftLength), 256));
            $result .= \chr($octet);
        }

        return $result;
    }

    public function __toString(): string
    {
        return (string)$this->value;
    }

    public function setValue(Content $content)
    {
        $binaryData    = $content->getBinary();
        $offsetIndex   = 0;
        $contentLength = $this->contentLength->getLength();
        $isNegative    = (\ord($binaryData[$offsetIndex]) & 0x80) != 0x00;
        $number        = gmp_init(\ord($binaryData[$offsetIndex++]) & 0x7F, 10);

        for ($i = 0; $i < $contentLength - 1; $i++) {
            $number = gmp_or(gmp_mul($number, 0x100), \ord($binaryData[$offsetIndex++]));
        }

        if ($isNegative) {
            $number = gmp_sub($number, gmp_pow(2, 8 * $contentLength - 1));
        }

        $value = gmp_strval($number);

        $this->value = $value;
    }

    public static function create($integer, $options = []): self
    {
        $isConstructed = false;
        $lengthForm    = ContentLength::SHORT_FORM;

        if (\is_int($integer) === false && preg_match('/^([+-]?[1-9]\d*|0)$/', $integer) == false) {
            throw new Exception("Invalid value [{$integer}] for ASN.1 Integer");
        }

        return
            ElementBuilder::createObject(
                Identifier::CLASS_UNIVERSAL,
                static::getType(),
                $isConstructed,
                $integer,
                $lengthForm
            );
    }
}
