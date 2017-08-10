<?php

namespace FG\ASN1\Exception;

use PHPUnit\Framework\TestCase;

/**
 *
 */
class ParserExceptionTest extends TestCase
{
    public function testGetOffset()
    {
        $e = new ParserException('Message', 56);
        $this->assertSame(56, $e->getOffset());
    }
}
