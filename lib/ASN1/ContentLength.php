<?php
/**
 * Created by PhpStorm.
 * User: 109
 * Date: 21.10.2015
 * Time: 11:33
 */

namespace FG\ASN1;


use Symfony\Component\Yaml\Exception\ParseException;

class ContentLength extends ObjectPart
{
    const SHORT_FORM = 1;
    const INDEFINITE_FORM = 2;
    const LONG_FORM = 3;


    public $binaryData;
    public $form;
    public $length;

    /**
     * @param $lengthOctets
     */
    public function __construct($lengthOctets)
    {
        $this->binaryData = $lengthOctets;
        $this->form = self::defineForm();
        $this->length = $this->calculateContentLength();
    }

    public function defineForm() {

        $firstOctet = ord(substr($this->binaryData, 0 , 1));

        if($firstOctet === 0x80) {
            $form = self::INDEFINITE_FORM;
        }else if(($firstOctet & 0x80) != 0) {
            $form = self::LONG_FORM;
        } else {
            $form = self::SHORT_FORM;
        }

        return $form;
    }

    public function calculateContentLength()
    {
        $firstOctet = substr($this->binaryData, 0, 1);

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
                throw new ParseException('Unknown Form');
        }

        return $contentLength;
    }
}