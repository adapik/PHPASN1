<?php

namespace FG\ASN1;


class Content extends ObjectPart implements ContentInterface
{
    public function __construct($binaryData)
    {
        $this->binaryData = $binaryData;
    }
}