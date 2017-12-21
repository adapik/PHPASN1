<?php
/*
 * This file is part of the PHPASN1 library.
 *
 * Copyright © Friedrich Große <friedrich.grosse@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace FG\ASN1;

use Exception;

abstract class AbstractCharacterString extends ASN1Object implements CharacterStringInterface
{
    /** @var string */
    protected $value;
    private $checkStringForIllegalChars = true;
    private $allowedCharacters = [];

    const STRING_TYPE_SIZE = [
        Identifier::UTF8_STRING       => 0,
        Identifier::BMP_STRING        => 2,
        Identifier::UNIVERSAL_STRING  => 0,
        Identifier::PRINTABLE_STRING  => 1,
        Identifier::IA5_STRING        => 1,
        Identifier::VISIBLE_STRING    => 1,
        Identifier::CHARACTER_STRING  => 0,
        Identifier::GENERAL_STRING    => 0,
        Identifier::GRAPHIC_STRING    => 0,
        Identifier::NUMERIC_STRING    => 0,
        Identifier::OBJECT_DESCRIPTOR => 0,
        Identifier::PRINTABLE_STRING  => 0,
        Identifier::T61_STRING        => 0
    ];

    public function __construct(
        Identifier $identifier,
        ContentLength $contentLength,
        Content $content,
        array $children
    ) {
        parent::__construct($identifier, $contentLength, $content, $children);

        $this->value = $this->getBinaryContent();
        if (count($this->allowedCharacters) > 0) {
            $this->checkString();
        }
    }

    protected function allowCharacter(string $character)
    {
        $this->allowedCharacters[] = $character;
    }

    protected function allowCharacters(string ...$characters)
    {
        foreach ($characters as $character) {
            $this->allowedCharacters[] = $character;
        }
    }

    protected function allowNumbers()
    {
        foreach (range('0', '9') as $char) {
            $this->allowedCharacters[] = (string) $char;
        }
    }

    protected function allowAllLetters()
    {
        $this->allowSmallLetters();
        $this->allowCapitalLetters();
    }

    protected function allowSmallLetters()
    {
        array_push($this->allowedCharacters, ...range('a', 'z'));
    }

    protected function allowCapitalLetters()
    {
        array_push($this->allowedCharacters, ...range('A', 'Z'));
    }

    protected function allowSpaces()
    {
        $this->allowedCharacters[] = ' ';
    }

    protected function allowAll()
    {
        $this->checkStringForIllegalChars = false;
    }

    public static function encodeValue($value)
    {
        return self::convert($value, Identifier::UTF8_STRING, static::getType());
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return self::convert($this->value, static::getType());
    }

    protected function checkString()
    {
        $stringLength = $this->getContentLength()->getLength();
        for ($i = 0; $i < $stringLength; $i++) {
            if (in_array($this->value[$i], $this->allowedCharacters, true) === false) {
                $typeName = IdentifierManager::getName($this->identifier->getTagNumber());
                throw new Exception(
                    "Could not create a {$typeName} from the character sequence '{$this->value}'. ".
                    "Symbol {$this->value[$i]}"
                );
            }
        }
    }

    public static function createFromString(string $string, $options = [])
    {
        $isConstructed = $options['isConstructed'] ?? false;
        $lengthForm    = $options['lengthForm'] ?? ContentLength::SHORT_FORM;
        return
            ElementBuilder::createObject(
                Identifier::CLASS_UNIVERSAL,
                static::getType(),
                $isConstructed,
                $string,
                $lengthForm
            );
    }

    public static function isValid(string $string)
    {
        try {
            static::createFromString($string);

            return true;
        } catch (\Exception $exception) {
            return false;
        }
    }

    /**
     * String type conversion
     *
     * This is a lazy conversion, dealing only with character size.
     * No real conversion table is used.
     *
     * @param string $in
     * @param int $from
     * @param int $to
     * @return string
     * @access public
     */
    protected static function convert($in, $from = Identifier::UTF8_STRING, $to = Identifier::UTF8_STRING)
    {
        if (!array_key_exists($from, self::STRING_TYPE_SIZE) || !array_key_exists($to, self::STRING_TYPE_SIZE)) {
            return false;
        }

        $insize  = self::STRING_TYPE_SIZE[$from];
        $outsize = self::STRING_TYPE_SIZE[$to];

        if ($insize === $outsize) {
            return $in;
        }

        $inlength = strlen($in);
        $out = '';

        for ($i = 0; $i < $inlength;) {
            if ($inlength - $i < $insize) {
                return false;
            }

            // Get an input character as a 32-bit value.
            $c = ord($in[$i++]);
            switch (true) {
                case $insize == 4:
                    $c = ($c << 8) | ord($in[$i++]);
                    $c = ($c << 8) | ord($in[$i++]);
                case $insize == 2:
                    $c = ($c << 8) | ord($in[$i++]);
                case $insize == 1:
                    break;
                case ($c & 0x80) == 0x00:
                    break;
                case ($c & 0x40) == 0x00:
                    return false;
                default:
                    $bit = 6;
                    do {
                        if ($bit > 25 || $i >= $inlength || (ord($in[$i]) & 0xC0) != 0x80) {
                            return false;
                        }
                        $c = ($c << 6) | (ord($in[$i++]) & 0x3F);
                        $bit += 5;
                        $mask = 1 << $bit;
                    } while ($c & $bit);
                    $c &= $mask - 1;
                    break;
            }

            // Convert and append the character to output string.
            $v = '';
            switch (true) {
                case $outsize == 4:
                    $v .= chr($c & 0xFF);
                    $c >>= 8;
                    $v .= chr($c & 0xFF);
                    $c >>= 8;
                case $outsize == 2:
                    $v .= chr($c & 0xFF);
                    $c >>= 8;
                case $outsize == 1:
                    $v .= chr($c & 0xFF);
                    $c >>= 8;
                    if ($c) {
                        return false;
                    }
                    break;
                case ($c & 0x80000000) != 0:
                    return false;
                case $c >= 0x04000000:
                    $v .= chr(0x80 | ($c & 0x3F));
                    $c = ($c >> 6) | 0x04000000;
                case $c >= 0x00200000:
                    $v .= chr(0x80 | ($c & 0x3F));
                    $c = ($c >> 6) | 0x00200000;
                case $c >= 0x00010000:
                    $v .= chr(0x80 | ($c & 0x3F));
                    $c = ($c >> 6) | 0x00010000;
                case $c >= 0x00000800:
                    $v .= chr(0x80 | ($c & 0x3F));
                    $c = ($c >> 6) | 0x00000800;
                case $c >= 0x00000080:
                    $v .= chr(0x80 | ($c & 0x3F));
                    $c = ($c >> 6) | 0x000000C0;
                default:
                    $v .= chr($c);
                    break;
            }
            $out .= strrev($v);
        }
        return $out;
    }
}
