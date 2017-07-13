<?php
/**
 * Created by PhpStorm.
 * User: 109
 * Date: 21.10.2015
 * Time: 12:58
 */

namespace FG\ASN1;


class Content extends ObjectPart
{
    public function __construct($binaryData)
    {
        $this->binaryData = $binaryData;
    }
}