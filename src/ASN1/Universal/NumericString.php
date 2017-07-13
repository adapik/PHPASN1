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

use FG\ASN1\AbstractCharacterString;
use FG\ASN1\Identifier;
use FG\ASN1\ContentLength;
use FG\ASN1\Content;

class NumericString extends AbstractCharacterString
{
    /**
     * Creates a new ASN.1 NumericString.
     * The following characters are permitted:
     * Digits                0,1, ... 9
     * SPACE                 (space)
     *
     * @param string $string
     */
    public function __construct(Identifier $identifier, ContentLength $contentLength, Content $content, array $children = [])
    {
        $this->allowNumbers();
        $this->allowSpaces();

        parent::__construct($identifier, $contentLength, $content, $children);
    }

    public static function getType()
    {
        return Identifier::NUMERIC_STRING;
    }
}
