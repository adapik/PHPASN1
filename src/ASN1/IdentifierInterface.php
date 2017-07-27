<?php

namespace FG\ASN1;

/**
 *
 */
interface IdentifierInterface extends ObjectPartInterface
{
    public function getTagNumber(): int;

    public function getTagClass();
}
