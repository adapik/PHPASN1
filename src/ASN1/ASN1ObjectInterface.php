<?php

namespace FG\ASN1;

/**
 *
 */
interface ASN1ObjectInterface
{
    public function getIdentifier(): IdentifierInterface;

    public function getContent(): ContentInterface;

    public function getContentLength(): ContentLengthInterface;

    public function getChildren(): array;
}
