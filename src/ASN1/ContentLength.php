<?php

namespace FG\ASN1;

class ContentLength extends ObjectPart implements ContentLengthInterface
{
    const SHORT_FORM      = 1;
    const INDEFINITE_FORM = 2;
    const LONG_FORM       = 3;

    private $form;
    private $length;

    /**
     * @param      $lengthOctets
     * @param null $length Default - be calculated, otherwise shold be passed with length octets
     */
    public function __construct($lengthOctets, $length = null)
    {
        $this->binaryData = $lengthOctets;
        $this->form = $this->defineForm();
        $this->length = $length ?: $this->calculateContentLength();
    }

    /**
     * Define length form based on binaryData
     * @return int
     */
    public function defineForm()
    {
        $firstOctet = \ord($this->binaryData[0]);

        if ($firstOctet < 0x80) {
            return self::SHORT_FORM;
        }

        if ($firstOctet === 0x80) {
            return self::INDEFINITE_FORM;
        }

        return self::LONG_FORM;
    }

    /**
     * Calculates content length based on binaryData
     * @return int lenght in octets
     */
    public function calculateContentLength()
    {
        $contentLength = \ord($this->binaryData[0]);

        switch ($this->form) {
            case self::SHORT_FORM:
                return $contentLength;
            case self::LONG_FORM:
                $nrOfLengthOctets = $contentLength & 0x7F;
                $contentLength    = 0;
                for ($i = 0; $i < $nrOfLengthOctets;) {
                    $contentLength = ($contentLength << 8) + \ord($this->binaryData[++$i]);
                }
                return $contentLength;
            case self::INDEFINITE_FORM:
                return NAN;
            default:
                throw new \Exception('Unknown Form');
        }
    }

    public function getLength()
    {
        return $this->length;
    }

    public function setLength(int $nrOfContentOctets)
    {
        $this->length = $nrOfContentOctets;
    }

    public function getLengthForm()
    {
        return $this->form;
    }
}
