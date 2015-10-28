<?php
/**
 * Created by PhpStorm.
 * User: 109
 * Date: 21.10.2015
 * Time: 17:31
 */

namespace FG\ASN1;


class EOC extends ObjectPart
{
    public function __construct()
    {
        $this->binaryData = chr(0) . chr(0);
    }
}