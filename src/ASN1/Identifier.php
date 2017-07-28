<?php

namespace FG\ASN1;


class Identifier extends ObjectPart implements IdentifierInterface
{
    public $tagClass;
    private $tagNumber;
    public $isConstructed;
    public $code;

    const CLASS_UNIVERSAL        = 0x00;
    const CLASS_APPLICATION      = 0x01;
    const CLASS_CONTEXT_SPECIFIC = 0x02;
    const CLASS_PRIVATE          = 0x03;

    const EOC               = 0x00; // unsupported for now
    const BOOLEAN           = 0x01;
    const INTEGER           = 0x02;
    const BITSTRING         = 0x03;
    const OCTETSTRING       = 0x04;
    const NULL              = 0x05;
    const OBJECT_IDENTIFIER = 0x06;
    const OBJECT_DESCRIPTOR = 0x07;
    const EXTERNAL          = 0x08; // unsupported for now
    const REAL              = 0x09; // unsupported for now
    const ENUMERATED        = 0x0A;
    const EMBEDDED_PDV      = 0x0B; // unsupported for now
    const UTF8_STRING       = 0x0C;
    const RELATIVE_OID      = 0x0D;
    // value 0x0E and 0x0F are reserved for future use

    const SEQUENCE         = 0x10;
    const SET              = 0x11;
    const NUMERIC_STRING   = 0x12;
    const PRINTABLE_STRING = 0x13;
    const T61_STRING       = 0x14; // sometimes referred to as TeletextString
    const VIDEOTEXT_STRING = 0x15;
    const IA5_STRING       = 0x16;
    const UTC_TIME         = 0x17;
    const GENERALIZED_TIME = 0x18;
    const GRAPHIC_STRING   = 0x19;
    const VISIBLE_STRING   = 0x1A;
    const GENERAL_STRING   = 0x1B;
    const UNIVERSAL_STRING = 0x1C;
    const CHARACTER_STRING = 0x1D; // Unrestricted character type
    const BMP_STRING       = 0x1E;

    const LONG_FORM      = 0x1F;
    const IS_CONSTRUCTED = 0x20;

    const ANY    = -1;
    const CHOICE = -2;

    /**
     * @param $identifier
     */
    public function __construct($identifierOctets)
    {
        $firstOctet = substr($identifierOctets, 0, 1);

        $this->binaryData    = $identifierOctets;
        $this->isConstructed = IdentifierManager::isConstructed($firstOctet);
        $this->tagClass      = ord($firstOctet) >> 6;
        $this->tagNumber     = IdentifierManager::getTagNumber($identifierOctets);
        $this->code          = $this->getCode();
    }

    public function getTagNumber(): int
    {
        return $this->tagNumber;
    }

    public function getTagClass()
    {
        return $this->tagClass;
    }

    public function getCode()
    {
        if ($this->tagClass === self::CLASS_UNIVERSAL) {
            switch ($this->tagNumber) {
                case self::EOC:
                    return 'End-of-contents octet';
                case self::BOOLEAN:
                    return 'Boolean';
                case self::INTEGER:
                    return 'Integer';
                case self::BITSTRING:
                    return 'Bit String';
                case self::OCTETSTRING:
                    return 'Octet String';
                case self::NULL:
                    return 'NULL';
                case self::OBJECT_IDENTIFIER:
                    return 'Object Identifier';
                case self::OBJECT_DESCRIPTOR:
                    return 'Object Descriptor';
                case self::EXTERNAL:
                    return 'External Type';
                case self::REAL:
                    return 'Real';
                case self::ENUMERATED:
                    return 'Enumerated';
                case self::EMBEDDED_PDV:
                    return 'Embedded PDV';
                case self::UTF8_STRING:
                    return 'UTF8 String';
                case self::RELATIVE_OID:
                    return 'Relative OID';
                case self::SEQUENCE:
                    return 'Sequence';
                case self::SET:
                    return 'Set';
                case self::NUMERIC_STRING:
                    return 'Numeric String';
                case self::PRINTABLE_STRING:
                    return 'Printable String';
                case self::T61_STRING:
                    return 'T61 String';
                case self::VIDEOTEXT_STRING:
                    return 'Videotext String';
                case self::IA5_STRING:
                    return 'IA5 String';
                case self::UTC_TIME:
                    return 'UTC Time';
                case self::GENERALIZED_TIME:
                    return 'Generalized Time';
                case self::GRAPHIC_STRING:
                    return 'Graphic String';
                case self::VISIBLE_STRING:
                    return 'Visible String';
                case self::GENERAL_STRING:
                    return 'General String';
                case self::UNIVERSAL_STRING:
                    return 'Universal String';
                case self::CHARACTER_STRING:
                    return 'Character String';
                case self::BMP_STRING:
                    return 'BMP String';

                case 0x0E:
                    return 'RESERVED (0x0E)';
                case 0x0F:
                    return 'RESERVED (0x0F)';
            }
        }

        if ($this->tagClass === self::CLASS_CONTEXT_SPECIFIC) {
            return '[' . $this->tagNumber . ']';
        }

        if ($this->tagClass === self::CLASS_APPLICATION) {
            return 'APPLICATION_' . $this->tagNumber;
        }

        if ($this->tagClass === self::CLASS_PRIVATE) {
            return 'PRIVATE_' . $this->tagNumber;
        }

        return 'UNKNOWN TAG';
    }

    public function isConstructed()
    {
        return $this->isConstructed;
    }
}