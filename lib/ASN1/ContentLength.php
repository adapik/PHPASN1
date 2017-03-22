<?php
/**
 * Created by PhpStorm.
 * User: 109
 * Date: 21.10.2015
 * Time: 11:33
 */

namespace FG\ASN1;


use FG\ASN1\Exception\ParserException;
use Symfony\Component\Yaml\Exception\ParseException;

class ContentLength extends ObjectPart
{
    const SHORT_FORM      = 1;
    const INDEFINITE_FORM = 2;
    const LONG_FORM       = 3;


    public $binaryData;
    public $form;
    public $length;

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
     *
     * @return int
     */
    public function defineForm() {

        $firstOctet = ord($this->binaryData[0]);

        if ($firstOctet === 0x80) {
            $form = self::INDEFINITE_FORM;
        } elseif (($firstOctet & 0x80) != 0) {
            $form = self::LONG_FORM;
        } else {
            $form = self::SHORT_FORM;
        }

        return $form;
    }

    /**
     * Calculates content length based on binaryData
     *
     * @return int lenght in octets
     */
    public function calculateContentLength()
    {
        $firstOctet = $this->binaryData[0];

        switch ($this->form) {
            case self::SHORT_FORM:
                $contentLength = ord($firstOctet);
                break;
            case self::LONG_FORM:
                $offsetIndex      = 0;
                $nrOfLengthOctets = ord($firstOctet) & 0x7F;
                $contentLength    = 0x00;
                for ($i = 0; $i < $nrOfLengthOctets; ++$i) {
                    $contentLength = ($contentLength * 256) + ord($this->binaryData[++$offsetIndex]);
                }
                break;
            case self::INDEFINITE_FORM:
                $contentLength = NAN;
                break;
            default:
                throw new \Exception('Unknown Form');
        }

        return $contentLength;
    }
}