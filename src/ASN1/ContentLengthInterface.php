<?php

namespace FG\ASN1;

/**
 *
 */
interface ContentLengthInterface extends ObjectPartInterface
{
    public function getLength();

    public function getLengthForm();
}
