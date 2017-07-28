<?php

namespace FG\ASN1;

class EOC extends ObjectPart
{
    public function __construct()
    {
        $this->binaryData = chr(0) . chr(0);
    }
}
