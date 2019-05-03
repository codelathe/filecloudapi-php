<?php

namespace CodeLathe\FileCloudApi\Tests;

use codelathe\fccloudapi\MetadataAttributeTypes;
use codelathe\fccloudapi\MetadataAttributeTypeCasterTrait;
use PHPUnit\Framework\TestCase;

class MetadataAttributeTypeCasterTraitTest extends TestCase
{
    public function testCastInt()
    {
        /** @var MetadataAttributeTypeCasterTrait $mock */
        $mock = $this->getMockForTrait(MetadataAttributeTypeCasterTrait::class);
        
        $this->assertSame(1, $mock->castToType('1', MetadataAttributeTypes::TYPE_INTEGER));
        $this->assertSame(1, $mock->castToType(1, MetadataAttributeTypes::TYPE_INTEGER));
        $this->assertSame(null, $mock->castToType('', MetadataAttributeTypes::TYPE_INTEGER));
    }

    public function testCastFloat()
    {
        /** @var MetadataAttributeTypeCasterTrait $mock */
        $mock = $this->getMockForTrait(MetadataAttributeTypeCasterTrait::class);

        $this->assertSame(1.1, $mock->castToType('1.1', MetadataAttributeTypes::TYPE_DECIMAL));
        $this->assertSame(1.1, $mock->castToType(1.1, MetadataAttributeTypes::TYPE_DECIMAL));
        $this->assertSame(null, $mock->castToType('', MetadataAttributeTypes::TYPE_DECIMAL));
    }
    
    public function testCastArray()
    {
        /** @var MetadataAttributeTypeCasterTrait $mock */
        $mock = $this->getMockForTrait(MetadataAttributeTypeCasterTrait::class);

        $this->assertSame(['foo', 'bar', 'baz'], $mock->castToType('foo,bar,baz', MetadataAttributeTypes::TYPE_ARRAY));
        $this->assertSame([], $mock->castToType('', MetadataAttributeTypes::TYPE_ARRAY));
    }
}