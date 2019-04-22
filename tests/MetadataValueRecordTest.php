<?php

/*******************************************************************************
 * Copyright (c) 2019 CodeLathe LLC. All rights Reserved.
 * This file is part of FileCloud  http://www.getfilecloud.com
 *******************************************************************************/

namespace CodeLathe\FileCloudApi\Tests;

use CodeLathe\FileCloudApi\MetadataValueRecord;
use PHPUnit\Framework\TestCase;

class MetadataValueRecordTest extends TestCase
{
    public function testInitialValuesAreSet()
    {
        $record = new MetadataValueRecord($this->getData());

        $this->assertSame('5cb73b04adccf621f8014968', $record->getId());
        $this->assertSame('Readable', $record->getName());
        $this->assertSame('Readable files metadataset', $record->getDescription());
        $this->assertSame(3, $record->getSetType());
        $this->assertSame(true, $record->isRead());
        $this->assertSame(true, $record->isWrite());
        $this->assertSame(count($record->getAttributes()), $record->getAttributesTotal());
        $this->assertIsArray($record->getAttributes());

        $expectedAttributes = $this->getExpectedAttributes();

        /** @var array $attribute */
        foreach ($record->getAttributes() as $j => $attribute) {
            foreach ($attribute as $k => $element) {
                if (is_object($element)) {
                    // DateTime
                    $this->assertEquals($expectedAttributes[$j][$k], $element);
                } else {
                    $this->assertSame($expectedAttributes[$j][$k], $element, "Key: $k");
                }
            }
        }
    }
    
    private function getData()
    {
        return [
            'id' => '5cb73b04adccf621f8014968',
            'name' => 'Readable',
            'description' => 'Readable files metadataset',
            'settype' => '3',
            'read' => '1',
            'write' => '1',
            'attribute0_attributeid' => '5cb73b04adccf621f8014961',
            'attribute0_name' => 'Summary',
            'attribute0_description' => 'A short summary of this readable',
            'attribute0_disabled' => '',
            'attribute0_required' => '1',
            'attribute0_datatype' => '1',
            'attribute0_value' => 'Unsummarized',
            'attribute1_attributeid' => '5cb73b04adccf621f8014962',
            'attribute1_name' => 'Pages',
            'attribute1_description' => '',
            'attribute1_disabled' => '',
            'attribute1_required' => '1',
            'attribute1_datatype' => '2',
            'attribute1_value' => '1192',
            'attribute2_attributeid' => '5cb73b04adccf621f8014963',
            'attribute2_name' => 'Size',
            'attribute2_description' => 'File size in MB',
            'attribute2_disabled' => '',
            'attribute2_required' => '1',
            'attribute2_datatype' => '3',
            'attribute2_value' => '5.5',
            'attribute3_attributeid' => '5cb73b04adccf621f8014964',
            'attribute3_name' => 'English',
            'attribute3_description' => '',
            'attribute3_disabled' => '',
            'attribute3_required' => '',
            'attribute3_datatype' => '4',
            'attribute3_value' => 'false', // better false than true for testing
            'attribute4_attributeid' => '5cb73b04adccf621f8014965',
            'attribute4_name' => 'Publish date',
            'attribute4_description' => '',
            'attribute4_disabled' => '',
            'attribute4_required' => '1',
            'attribute4_datatype' => '5',
            'attribute4_value' => '1987-07-15 00:00:00',
            'attribute5_attributeid' => '5cb73b04adccf621f8014966',
            'attribute5_name' => 'Format',
            'attribute5_description' => '',
            'attribute5_disabled' => '',
            'attribute5_required' => '',
            'attribute5_datatype' => '6',
            'attribute5_value' => 'PDF',
            'attribute5_enumvalues' => 'PDF,Kindle,Text,Markdown',
            'attribute6_attributeid' => '5cb73b04adccf621f8014967',
            'attribute6_name' => 'Tags',
            'attribute6_description' => '',
            'attribute6_disabled' => '',
            'attribute6_required' => '',
            'attribute6_datatype' => '7',
            'attribute6_value' => 'Self-help,Philosophy',
            'attributes_total' => '7',
        ];
    }

    private function getExpectedAttributes()
    {
        return [
            0 => [
                'attributeid' => '5cb73b04adccf621f8014961',
                'name' => 'Summary',
                'description' => 'A short summary of this readable',
                'disabled' => false,
                'required' => true,
                'datatype' => 1,
                'value' => 'Unsummarized',
            ],
            1 => [
                'attributeid' => '5cb73b04adccf621f8014962',
                'name' => 'Pages',
                'description' => '',
                'disabled' => false,
                'required' => true,
                'datatype' => 2,
                'value' => 1192,
            ],
            2 => [
                'attributeid' => '5cb73b04adccf621f8014963',
                'name' => 'Size',
                'description' => 'File size in MB',
                'disabled' => false,
                'required' => true,
                'datatype' => 3,
                'value' => 5.5,
            ],
            3 => [
                'attributeid' => '5cb73b04adccf621f8014964',
                'name' => 'English',
                'description' => '',
                'disabled' => false,
                'required' => false,
                'datatype' => 4,
                'value' => false,
            ],
            4 => [
                'attributeid' => '5cb73b04adccf621f8014965',
                'name' => 'Publish date',
                'description' => '',
                'disabled' => false,
                'required' => true,
                'datatype' => 5,
                'value' => \DateTime::createFromFormat('Y-m-d H:i:s', '1987-07-15 00:00:00'),
            ],
            5 => [
                'attributeid' => '5cb73b04adccf621f8014966',
                'name' => 'Format',
                'description' => '',
                'disabled' => false,
                'required' => false,
                'datatype' => 6,
                'value' => 'PDF',
                'enumvalues' => ['PDF', 'Kindle', 'Text', 'Markdown']
            ],
            6 => [
                'attributeid' => '5cb73b04adccf621f8014967',
                'name' => 'Tags',
                'description' => '',
                'disabled' => false,
                'required' => false,
                'datatype' => 7,
                'value' => ['Self-help', 'Philosophy'],
            ],
        ];
    }
}