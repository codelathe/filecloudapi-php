<?php

namespace CodeLathe\FileCloudApi\Tests;

use codelathe\fccloudapi\AbstractMetadataRecord;
use codelathe\fccloudapi\MetadataAttributeTypeCasterTrait;
use PHPUnit\Framework\TestCase;

class MetadataAttributeTypeCasterTraitTest extends TestCase
{
    public function testCastInt()
    {
        /** @var MetadataAttributeTypeCasterTrait $mock */
        $mock = $this->getMockForTrait(MetadataAttributeTypeCasterTrait::class);
        
        $this->assertSame(1, $mock->castToType('1', AbstractMetadataRecord::TYPE_INTEGER));
        $this->assertSame(1, $mock->castToType(1, AbstractMetadataRecord::TYPE_INTEGER));
        $this->assertSame(null, $mock->castToType('', AbstractMetadataRecord::TYPE_INTEGER));
    }

    public function testCastFloat()
    {
        /** @var MetadataAttributeTypeCasterTrait $mock */
        $mock = $this->getMockForTrait(MetadataAttributeTypeCasterTrait::class);

        $this->assertSame(1.1, $mock->castToType('1.1', AbstractMetadataRecord::TYPE_DECIMAL));
        $this->assertSame(1.1, $mock->castToType(1.1, AbstractMetadataRecord::TYPE_DECIMAL));
        $this->assertSame(null, $mock->castToType('', AbstractMetadataRecord::TYPE_DECIMAL));
    }
    
    public function testCastArray()
    {
        /** @var MetadataAttributeTypeCasterTrait $mock */
        $mock = $this->getMockForTrait(MetadataAttributeTypeCasterTrait::class);

        $this->assertSame(['foo', 'bar', 'baz'], $mock->castToType('foo,bar,baz', AbstractMetadataRecord::TYPE_ARRAY));
        $this->assertSame([], $mock->castToType('', AbstractMetadataRecord::TYPE_ARRAY));
    }
}