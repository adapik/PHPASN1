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
use FG\ASN1\Exception\ParserException;
use FG\ASN1\ContentLength;
use FG\ASN1\Content;

class RelativeObjectIdentifier extends ObjectIdentifier implements Parsable
{
    public static function getType()
    {
        return Identifier::RELATIVE_OID;
    }
}
