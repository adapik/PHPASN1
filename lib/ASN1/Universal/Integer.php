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
use FG\ASN1\Object;
use FG\ASN1\Identifier;
use FG\ASN1\ContentLength;
use FG\ASN1\Content;

class Integer extends Object
{
    /** @var int */
    public $value;

    /**
     * @param int $value
     *
     * @throws Exception if the value is not numeric
     */
    public function __construct(Identifier $identifier, ContentLength $contentLength, Content $content, array $children = [])
    {

        parent::__construct($identifier, $contentLength, $content, $children);

        $this->setValue($content);

    }

    public function getType()
    {
        return Identifier::INTEGER;
    }

    public function getContent()
    {
        return $this->value;
    }

    protected function calculateContentLength()
    {
        $nrOfOctets = 1; // we need at least one octet
        $tmpValue = gmp_abs(gmp_init($this->value, 10));
        while (gmp_cmp($tmpValue, 127) > 0) {
            $tmpValue = $this->rightShift($tmpValue, 8);
            $nrOfOctets++;
        }
        return $nrOfOctets;
    }

    /**
     * @param resource|\GMP $number
     * @param int $positions
     *
     * @return resource|\GMP
     */
    private function rightShift($number, $positions)
    {
        // Shift 1 right = div / 2
        return gmp_div($number, gmp_pow(2, (int) $positions));
    }

    protected function getEncodedValue()
    {
        $numericValue = gmp_init($this->value, 10);
        $contentLength = $this->getContentLength();

        if (gmp_sign($numericValue) < 0) {
            $numericValue = gmp_add($numericValue, (gmp_sub(gmp_pow(2, 8 * $contentLength), 1)));
            $numericValue = gmp_add($numericValue, 1);
        }

        $result = '';
        for ($shiftLength = ($contentLength - 1) * 8; $shiftLength >= 0; $shiftLength -= 8) {
            $octet = gmp_strval(gmp_mod($this->rightShift($numericValue, $shiftLength), 256));
            $result .= chr($octet);
        }

        return $result;
    }

    public function setValue(Content $content)
    {
        $binaryData = $content->binaryData;
        $offsetIndex = 0;
        $contentLength = $this->contentLength->length;
        $isNegative = (ord($binaryData[$offsetIndex]) & 0x80) != 0x00;
        $number = gmp_init(ord($binaryData[$offsetIndex++]) & 0x7F, 10);

        for ($i = 0; $i < $contentLength - 1; $i++) {
            $number = gmp_or(gmp_mul($number, 0x100), ord($binaryData[$offsetIndex++]));
        }

        if ($isNegative) {
            $number = gmp_sub($number, gmp_pow(2, 8 * $contentLength - 1));
        }

        $value = gmp_strval($number, 10);
        if (is_string($value)) {
            // remove gaps between hex digits
            $value = preg_replace('/\s|0x/', '', $value);
        } elseif (is_numeric($value)) {
            $value = dechex($value);
        } else {
            throw new Exception('OctetString: unrecognized input type!');
        }

        if (strlen($value) % 2 != 0) {
            // transform values like 1F2 to 01F2
            $value = '0'.$value;
        }

        $this->value = $value;
    }
}
