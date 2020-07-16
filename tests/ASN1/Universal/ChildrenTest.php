<?php
/**
 * ChildrenTest
 *
 * @author    Nurlan Mukhanov <nurike@gmail.com>
 * @copyright 2020 Nurlan Mukhanov
 * @license   https://en.wikipedia.org/wiki/MIT_License MIT License
 * @link      https://github.com/Adapik/CMS
 */

namespace FG\Test\ASN1\Universal;

use FG\ASN1\Exception\Exception;
use FG\ASN1\Universal\Integer;
use FG\ASN1\Universal\NullObject;
use FG\ASN1\Universal\Sequence;
use PHPUnit\Framework\TestCase;

class ChildrenTest extends TestCase
{
    public function testRemoveChild()
    {
        $unknownChild = Integer::create(0);

        // Creating same objects
        $object = Sequence::create([
            Sequence::create([NullObject::create()]),
            Integer::create(1),
            Sequence::create([NullObject::create()]),
            Integer::create(2),
            Sequence::create([NullObject::create()]), // to be removed.
            Integer::create(3),
            Sequence::create([NullObject::create()]),
            Integer::create(4),
        ]);

        $object->removeChild($object->getChildren()[4]);

        // Test that removed not first identical Sequence
        $this->assertInstanceOf(Sequence::class, $object->getChildren()[0]);

        // Check we really removed
        $this->assertCount(7, $object->getChildren());

        // check we have properly defined index
        $i = 0;
        foreach ($object->getChildren() as $index => $child) {

            $this->assertEquals($i, $index);
            $i++;
        }

        $this->expectException(Exception::class);

        $object->removeChild($unknownChild);
    }

    public function testReplaceChild()
    {
        $unknownChild = Integer::create(0);

        $childToReplace = Sequence::create([NullObject::create()]);

        $object = Sequence::create([
            NullObject::create(),
            $childToReplace,
            NullObject::create(),
        ]);

        $replacement = NullObject::create();

        $object->replaceChild($childToReplace, $replacement);

        // New child now NullObject instead of Sequence
        $this->assertInstanceOf(NullObject::class, $object->getChildren()[0]);

        // We still have 1 child
        $this->assertCount(3, $object->getChildren());

        // check we have properly defined index
        $i = 0;
        foreach ($object->getChildren() as $index => $child) {

            $this->assertEquals($i, $index);
            $i++;
        }

        // Exception if we trying replace unknown child
        $this->expectException(Exception::class);
        $object->replaceChild($unknownChild, $replacement);
    }

    public function testAppendChild()
    {
        $childToAppend = Integer::create(0);
        $childrenToAppend = [Integer::create(1), Integer::create(2), Integer::create(3)];

        $object = Sequence::create([
            NullObject::create()
        ]);

        $object->appendChild($childToAppend);
        // We have +1 now
        $this->assertCount(2, $object->getChildren());
        // First child still NullObject
        $this->assertInstanceOf(NullObject::class, $object->getChildren()[0]);
        // Second child is Integer
        $this->assertInstanceOf(Integer::class, $object->getChildren()[1]);

        $object->appendChild($childrenToAppend);
        // We have +1 + 3 now
        $this->assertCount(5, $object->getChildren());
        /** @var Integer[] $integers */
        $integers = $object->findChildrenByType(Integer::class);

        // We have 4 children
        $this->assertCount(4, $integers);

        $i = 0;
        foreach ($integers as $integer) {
            // Check integers have been properly positioned
            $this->assertEquals($i, intval($integer->__toString()));
            $i++;
        }

        // check we have properly defined index
        $i = 0;
        foreach ($object->getChildren() as $index => $child) {

            $this->assertEquals($i, $index);
            $i++;
        }
    }
}
