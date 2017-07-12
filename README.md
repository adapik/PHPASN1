PHPASN1
=======

[![Build Status](https://secure.travis-ci.org/Adapik/PHPASN1.png?branch=master)](http://travis-ci.org/fgrosse/PHPASN1)
[![HHVM Status](http://hhvm.h4cc.de/badge/Adapik/phpasn1.png)](http://hhvm.h4cc.de/package/fgrosse/phpasn1)
[![PHP 7 ready](http://php7ready.timesplinter.ch/Adapik/PHPASN1/badge.svg)](https://travis-ci.org/fgrosse/PHPASN1)
[![Coverage Status](https://coveralls.io/repos/Adapik/PHPASN1/badge.svg?branch=master&service=github)](https://coveralls.io/github/fgrosse/PHPASN1?branch=master)

[![Latest Stable Version](https://poser.pugx.org/Adapik/phpasn1/v/stable.png)](https://packagist.org/packages/fgrosse/phpasn1)
[![Total Downloads](https://poser.pugx.org/Adapik/phpasn1/downloads.png)](https://packagist.org/packages/fgrosse/phpasn1)
[![Latest Unstable Version](https://poser.pugx.org/Adapik/phpasn1/v/unstable.png)](https://packagist.org/packages/fgrosse/phpasn1)
[![License](https://poser.pugx.org/Adapik/phpasn1/license.png)](https://packagist.org/packages/fgrosse/phpasn1)

A PHP Framework that allows you to encode and decode arbitrary [ASN.1][3] structures
using the [ITU-T X.690 Encoding Rules][4].
This encoding is very frequently used in [X.509 PKI environments][5] or the communication between heterogeneous computer systems.

The API allows you to encode ASN.1 structures to create binary data such as certificate
signing requests (CSR), X.509 certificates or certificate revocation lists (CRL).
PHPASN1 can also read [BER encoded][6] binary data into separate PHP objects that can be manipulated by the user and reencoded afterwards.

The **changelog** can now be found at [CHANGELOG.md](CHANGELOG.md).

## Dependencies

PHPASN1 requires at least `PHP 7` and the `gmp` extension.

## Installation

The preferred way to install this library is to rely on [Composer][2]:

```bash
$ composer require fgrosse/phpasn1
```

## Usage

### Encoding ASN.1 Structures

PHPASN1 offers you a class for each of the implemented ASN.1 universal types.
The constructors should be pretty self explanatory so you should have no big trouble getting started.
All data will be encoded using [DER encoding][8]

```php
use FG\ASN1\Universal\Integer;
use FG\ASN1\Universal\Boolean;
use FG\ASN1\Universal\Enumerated;
use FG\ASN1\Universal\IA5String;
use FG\ASN1\Universal\ObjectIdentifier;
use FG\ASN1\Universal\PrintableString;
use FG\ASN1\Universal\Sequence;
use FG\ASN1\Universal\Set;
use FG\ASN1\Universal\NullObject;

$integer = Integer::create(123456);        
$boolean = Boolean::create(true);
$enum = Enumerated::create(1);

$asnNull = NullObject::create();
$objectIdentifier1 = ObjectIdentifier('1.2.250.1.16.9');
$printableString = PrintableString::createFromString('Foo bar');

$sequence = Sequence::create([$integer, $boolean, $enum, $ia5String]);
$set = Set([$sequence, $asnNull, $objectIdentifier1, $objectIdentifier2, $printableString]);

$myBinary  = $sequence->getBinary();
$myBinary .= $set->getBinary();

echo base64_encode($myBinary);
```

### Decoding binary data

Decoding BER encoded binary data is just as easy as encoding it:

```php
use FG\ASN1\Object;

$base64String = ...
$binaryData = base64_decode($base64String);        
$asnObject = Object::fromBinary($binaryData);
// do stuff
```

If you already know exactly how your expected data should look like you can use the `FG\ASN1\TemplateParser`:

```php
use FG\ASN1\TemplateParser;

// first define your template
$template = [
    Identifier::SEQUENCE => [
        Identifier::SET => [
            Identifier::OBJECT_IDENTIFIER,
            Identifier::SEQUENCE => [
                Identifier::INTEGER,
                Identifier::BITSTRING,
            ]
        ]
    ]
];

// if your binary data is not matching the template you provided this will throw an `\Exception`:
$parser = new TemplateParser();
$object = $parser->parseBinary($data, $template);

// there is also a convenience function if you parse binary data from base64:
$object = $parser->parseBase64($data, $template);
```

You can use this function to make sure your data has exactly the format you are expecting.

### Thanks

To [all contributors][1] so far!

## License

This library is distributed under the [MIT License](LICENSE).

[1]: https://github.com/Adapik/PHPASN1/graphs/contributors
[2]: https://getcomposer.org/
[3]: http://www.itu.int/ITU-T/asn1/
[4]: http://www.itu.int/ITU-T/recommendations/rec.aspx?rec=x.690
[5]: http://en.wikipedia.org/wiki/X.509
[6]: http://en.wikipedia.org/wiki/X.690#BER_encoding
[7]: http://php.net/manual/en/book.curl.php
[8]: http://en.wikipedia.org/wiki/X.690#DER_encoding
[9]: https://styleci.io
[10]: https://coveralls.io/github/fgrosse/PHPASN1
[11]: https://github.com/fgrosse/PHPASN1/blob/master/tests/ASN1/TemplateParserTest.php#L16
[12]: https://groups.google.com/d/forum/phpasn1
