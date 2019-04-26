<?php

namespace CodeLathe\FileCloudApi\Tests;

use codelathe\fccloudapi\AdminMetadataSetRecord;
use PHPUnit\Framework\TestCase;

final class AdminMetadataSetRecordTest extends TestCase
{
    public function testInitialValuesAreSet()
    {
        $record = new AdminMetadataSetRecord($this->getData());

        $this->assertSame('5cc06b9fadccf621f80217d0', $record->getId());
        $this->assertSame('Review', $record->getName());
        $this->assertSame('Review description', $record->getDescription());
        $this->assertSame(3, $record->getType());
        $this->assertSame(false, $record->getDisabled());
        $this->assertSame(false, $record->getAllowAllPaths());
        
        $this->assertSame(count($record->getAttributes()), $record->getAttributesTotal());
        $this->assertIsArray($record->getAttributes());
        $expectedAttributes = $this->getExpectedAttributesSet();

        /** @var array $attribute */
        foreach ($record->getAttributes() as $j => $attribute) {
            foreach ($attribute as $k => $element) {
                if (is_object($element)) {
                    // DateTime
                    $this->assertEquals($expectedAttributes[$j][$k], $element);
                } else {
                    $this->assertSame($expectedAttributes[$j][$k], $element);
                }
            }
        }
        
        $this->assertSame(count($record->getUsers()), $record->getUsersTotal());
        $this->assertIsArray($record->getUsers());
        $expectedUsers = $this->getExpectedUserSet();

        /** @var array $user */
        foreach ($record->getUsers() as $j => $user) {
            foreach ($user as $k => $element) {
                $this->assertSame($expectedUsers[$j][$k], $element);
            }
        }
        
        $this->assertSame(count($record->getGroups()), $record->getGroupsTotal());
        $this->assertIsArray($record->getGroups());
        $expectedGroups = $this->getExpectedGroupSet();

        /** @var array $user */
        foreach ($record->getGroups() as $j => $group) {
            foreach ($group as $k => $element) {
                $this->assertSame($expectedGroups[$j][$k], $element);
            }
        }
        
        $this->assertSame(count($record->getPaths()), $record->getPathsTotal());
        $this->assertIsArray($record->getPaths());
        $expectedPaths = $this->getExpectedPathSet();

        /** @var array $user */
        foreach ($record->getPaths() as $j => $path) {
            $this->assertSame($expectedPaths[$j], $path);
        }
    }
    
    private function getData()
    {
        return [
            'id' => '5cc06b9fadccf621f80217d0',
            'name' => 'Review',
            'description' => 'Review description',
            'type' => '3',
            'disabled' => '',
            'allowallpaths' => '',
            'attribute0_attributeid' => '5cb73b04adccf621f8014961',
            'attribute0_name' => 'Summary',
            'attribute0_description' => 'A short summary of this readable',
            'attribute0_type' => '1',
            'attribute0_defaultvalue' => 'Unsummarized',
            'attribute0_required' => '1',
            'attribute0_disabled' => '',
            'attribute1_attributeid' => '5cb73b04adccf621f8014962',
            'attribute1_name' => 'Pages',
            'attribute1_description' => '',
            'attribute1_type' => '2',
            'attribute1_defaultvalue' => '1192',
            'attribute1_required' => '1',
            'attribute1_disabled' => '',
            'attribute2_attributeid' => '5cb73b04adccf621f8014963',
            'attribute2_name' => 'Size',
            'attribute2_description' => 'File size in MB',
            'attribute2_type' => '3',
            'attribute2_defaultvalue' => '5.5',
            'attribute2_required' => '1',
            'attribute2_disabled' => '',
            'attribute3_attributeid' => '5cb73b04adccf621f8014964',
            'attribute3_name' => 'English',
            'attribute3_description' => '',
            'attribute3_type' => '4',
            'attribute3_defaultvalue' => 'false',
            'attribute3_required' => '',
            'attribute3_disabled' => '',
            'attribute4_attributeid' => '5cb73b04adccf621f8014965',
            'attribute4_name' => 'Publish date',
            'attribute4_description' => '',
            'attribute4_type' => '5',
            'attribute4_defaultvalue' => '1987-07-15 00:00:00',
            'attribute4_required' => '1',
            'attribute4_disabled' => '',
            'attribute5_attributeid' => '5cb73b04adccf621f8014966',
            'attribute5_name' => 'Format',
            'attribute5_description' => '',
            'attribute5_type' => '6',
            'attribute5_defaultvalue' => 'PDF',
            'attribute5_required' => '',
            'attribute5_disabled' => '',
            'attribute5_predefinedvalue0' => 'PDF',
            'attribute5_predefinedvalue1' => 'Kindle',
            'attribute5_predefinedvalue2' => 'Text',
            'attribute5_predefinedvalue3' => 'Markdown',
            'attribute5_predefinedvalues_total' => '4',
            'attribute6_attributeid' => '5cb73b04adccf621f8014967',
            'attribute6_name' => 'Tags',
            'attribute6_description' => '',
            'attribute6_type' => '7',
            'attribute6_defaultvalue' => 'Self-help,Philosophy',
            'attribute6_required' => '',
            'attribute6_disabled' => '',
            'attributes_total' => '7',
            'user0_name' => 'user1',
            'user0_read' => '1',
            'user0_write' => '1',
            'users_total' => '1',
            'group0_id' => '55557777bbbbccccddddaaaa',
            'group0_name' => 'EVERYONE',
            'group0_read' => '1',
            'group0_write' => '1',
            'groups_total' => '1',
            'path0' => '/user1',
            'paths_total' => '1',
        ];
    }
    
    private function getExpectedAttributesSet()
    {
        return [
            0 => [
                'attributeid' => '5cb73b04adccf621f8014961',
                'name' => 'Summary',
                'description' => 'A short summary of this readable',
                'type' => 1,
                'defaultvalue' => 'Unsummarized',
                'required' => true,
                'disabled' => false,
            ],
            1 => [
                'attributeid' => '5cb73b04adccf621f8014962',
                'name' => 'Pages',
                'description' => '',
                'type' => 2,
                'defaultvalue' => 1192,
                'required' => true,
                'disabled' => false,
            ],
            2 => [
                'attributeid' => '5cb73b04adccf621f8014963',
                'name' => 'Size',
                'description' => 'File size in MB',
                'type' => 3,
                'defaultvalue' => 5.5,
                'required' => true,
                'disabled' => false,
            ],
            3 => [
                'attributeid' => '5cb73b04adccf621f8014964',
                'name' => 'English',
                'description' => '',
                'type' => 4,
                'defaultvalue' => false,
                'required' => false,
                'disabled' => false,
            ],
            4 => [
                'attributeid' => '5cb73b04adccf621f8014965',
                'name' => 'Publish date',
                'description' => '',
                'type' => 5,
                'defaultvalue' => \DateTime::createFromFormat('Y-m-d H:i:s', '1987-07-15 00:00:00'),
                'required' => true,
                'disabled' => false,
            ],
            5 => [
                'attributeid' => '5cb73b04adccf621f8014966',
                'name' => 'Format',
                'description' => '',
                'type' => 6,
                'defaultvalue' => 'PDF',
                'required' => false,
                'disabled' => false,
                'predefinedvalue' => ['PDF', 'Kindle', 'Text', 'Markdown'],
                'predefinedvalues_total' => 4
            ],
            6 => [
                'attributeid' => '5cb73b04adccf621f8014967',
                'name' => 'Tags',
                'description' => '',
                'type' => 7,
                'defaultvalue' => ['Self-help', 'Philosophy'],
                'required' => false,
                'disabled' => false,
            ],
        ];
    }
    
    private function getExpectedUserSet()
    {
        return [
            0 => [
                'name' => 'user1',
                'read' => true,
                'write' => true,
            ]
        ];
    }
    
    private function getExpectedGroupSet()
    {
        return [
            0 => [
                'id' => '55557777bbbbccccddddaaaa',
                'name' => 'EVERYONE',
                'read' => true,
                'write' => true,
            ]
        ];
    }
    
    private function getExpectedPathSet()
    {
        return [
            '/user1',
        ];
    }
}
