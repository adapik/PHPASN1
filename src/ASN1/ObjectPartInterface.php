<?php

namespace FG\ASN1;

/**
 *
 */
interface ObjectPartInterface
{
    public function getBinary(): string;

    public function getNrOfOctets(): int;
}
