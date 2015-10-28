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
use FG\ASN1\Parsable;
use FG\ASN1\Identifier;
use FG\ASN1\ContentLength;
use FG\ASN1\Content;

class BitString extends OctetString implements Parsable
{
    private $nrOfUnusedBits;

    public function __construct(Identifier $identifier, ContentLength $contentLength, Content $content, array $children = [])
    {

        parent::__construct($identifier, $contentLength, $content, $children);

        $this->nrOfUnusedBits = $nrOfUnusedBits = ord($content->binaryData[0]);

        if (!is_numeric($nrOfUnusedBits) || $nrOfUnusedBits < 0) {
            throw new Exception('BitString: second parameter needs to be a positive number (or zero)!');
        }
    }

    protected function getEncodedValue()
    {
        // the first octet determines the number of unused bits
        $nrOfUnusedBitsOctet = chr($this->nrOfUnusedBits);
        $actualContent = parent::getEncodedValue();

        return $nrOfUnusedBitsOctet.$actualContent;
    }

    public function getNumberOfUnusedBits()
    {
        return $this->nrOfUnusedBits;
    }

    public function setValue(Content $content)
    {
        $binaryData = $content->binaryData;
        $value = bin2hex(substr($binaryData, 1, $this->contentLength->length - 1));

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

        $this->value = bin2hex($value);
    }
}
