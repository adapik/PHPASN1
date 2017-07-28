<?php

namespace FG\ASN1;

abstract class ObjectPart
{
    protected $binaryData;

    public function getNrOfOctets(): int
    {
        return strlen($this->binaryData);
    }

    public function getBinary(): string
    {
        return $this->binaryData;
    }
}
