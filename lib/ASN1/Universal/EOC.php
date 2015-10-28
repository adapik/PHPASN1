<?php
/*
 * This file is part of the PHPASN1 library.
 *
 * Copyright © Friedrich Gro?e <friedrich.grosse@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FG\ASN1\Universal;

use FG\ASN1\Object;
use FG\ASN1\Parsable;
use FG\ASN1\Identifier;
use FG\ASN1\Exception\ParserException;

class EOC extends Object
{
    public function getType()
    {
        return Identifier::EOC;
    }

    protected function calculateContentLength()
    {
        return 0;
    }

    protected function getEncodedValue()
    {
        return null;
    }

    public function getContent()
    {
        return null;
    }
}
