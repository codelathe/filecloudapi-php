<?php

namespace CodeLathe\FileCloudApi\Tests\CloudApi;

use codelathe\fccloudapi\CloudAdminAPI;
use codelathe\fccloudapi\CommandRecord;
use CodeLathe\FileCloudApi\Tests\Fixtures\AccessibleCloudAdminApi;
use PHPUnit\Framework\TestCase;

/**
 * Class UpdateMetadaSetTest
 * @package CodeLathe\FileCloudApi\Tests\CloudApi
 */
class UpdateMetadaSetTest extends TestCase
{
    /**
     * Test whether it returns the correct data record for
     * a successful API request.
     */
    public function testReturns1OnSuccess()
    {
        $serverUrl = 'https://fcapi.example.com';
        $cloudApiMock = $this->getMockBuilder(AccessibleCloudAdminApi::class)
            ->setConstructorArgs([$serverUrl])
            ->setMethods(['init', '__destruct', 'doPost'])
            ->getMock();

        $mockApiResponse = <<<RESPONSE
<commands>
    <command>
        <type>updatemetadataset</type>
        <result>1</result>
        <message>Metadata set definition Review updated successfully</message>
    </command>
</commands>
RESPONSE;
        $mockApiRequest = $this->getValidApiRequest();

        $cloudApiMock->method('doPost')
            ->with("{$serverUrl}/admin/updatemetadataset", http_build_query($mockApiRequest))
            ->willReturn($mockApiResponse);

        /** @var CloudAdminAPI $cloudApiMock */
        /** @var CommandRecord $commandRecord */
        $commandRecord = $cloudApiMock->updateMetadataSet(...array_values($this->getValidArguments()));
        $this->assertEquals(1, $commandRecord->getResult());
        $this->assertEquals('updatemetadataset', $commandRecord->getType());
        $this->assertEquals('Metadata set definition Review updated successfully', $commandRecord->getMessage());
    }

    /**
     * Test whether it returns the correct data record for
     * an unsuccessful API request.
     */
    public function testReturns0OnFail()
    {
        $serverUrl = 'https://fcapi.example.com';
        $cloudApiMock = $this->getMockBuilder(AccessibleCloudAdminApi::class)
            ->setConstructorArgs([$serverUrl])
            ->setMethods(['init', '__destruct', 'doPost'])
            ->getMock();

        $mockApiResponse = <<<RESPONSE
<commands>
    <command>
        <type>updatemetadataset</type>
        <result>0</result>
        <message>Failed to update the given Metadata Set Definition: . Metadata set deifnition is invalid. Reason: Set Definition Name has to be specified</message>
    </command>
</commands>
RESPONSE;
        $mockApiRequest = $this->getValidApiRequest();
        $mockApiRequest['name'] = '';    // Simulate invalid request with a blank name
        

        $cloudApiMock->method('doPost')
            ->with("{$serverUrl}/admin/updatemetadataset", http_build_query($mockApiRequest))
            ->willReturn($mockApiResponse);

        $arguments = $this->getValidArguments();
        
        // Make request invalid with a blank name
        $arguments['name'] = '';
        
        /** @var CloudAdminAPI $cloudApiMock */
        /** @var CommandRecord $commandRecord */
        $commandRecord = $cloudApiMock->updateMetadataSet(...array_values($arguments));
        $this->assertEquals(0, $commandRecord->getResult());
        $this->assertEquals('updatemetadataset', $commandRecord->getType());
        $this->assertEquals(
            'Failed to update the given Metadata Set Definition: . Metadata set deifnition is invalid. Reason: Set Definition Name has to be specified',
            $commandRecord->getMessage()
        );
    }

    /**
     * Must be synced with the return of getValidApiRequest()
     * @return array
     * @throws \Exception
     */
    private function getValidArguments()
    {
        $args = [
            'id' => '5cc06b9fadccf621f80217d0',
            'name' => 'Review',
            'description' => 'Review description',
            'disabled' => false,
            'allowallpaths' => false,
            'type' => 3,
            'attributes' => [
                [
                    'attributeid' => '5cc06b9fadccf621f80217ce',
                    'name' => 'String',
                    'description' => 'String description',
                    'type' => 1,
                    'required' => true,
                    'disabled' => false,
                    'defaultvalue' => 'Default string',
                    'predefinedvalues_total' => 0,
                ],
                [
                    'attributeid' => '5cc09402adccf621f8021956',
                    'name' => 'Int',
                    'description' => '',
                    'type' => 2,
                    'required' => true,
                    'disabled' => false,
                    'defaultvalue' => 1000,
                    'predefinedvalues_total' => 0,
                ],
                [
                    'attributeid' => '5cc09402adccf621f8021957',
                    'name' => 'Dec',
                    'description' => '',
                    'type' => 3,
                    'required' => false,
                    'disabled' => false,
                    'defaultvalue' => 1.1,
                    'predefinedvalues_total' => 0,
                ],
                [
                    'attributeid' => '5cc09402adccf621f8021958',
                    'name' => 'Bool',
                    'description' => '',
                    'type' => 4,
                    'required' => false,
                    'disabled' => false,
                    'defaultvalue' => true,
                    'predefinedvalues_total' => 0,
                ],
                [
                    'attributeid' => '5cc09402adccf621f8021959',
                    'name' => 'Date',
                    'description' => '',
                    'type' => 5,
                    'required' => false,
                    'disabled' => false,
                    'defaultvalue' => new \DateTime('2019-04-03 00:00:00'),
                    'predefinedvalues_total' => 0,
                ],
                [
                    'attributeid' => '5cc09402adccf621f802195a',
                    'name' => 'Select',
                    'description' => '',
                    'type' => 6,
                    'required' => false,
                    'disabled' => false,
                    'defaultvalue' => 'A',
                    'predefinedvalue' => ['A', 'B', 'C'],
                    'predefinedvalues_total' => 3,
                ],
                [
                    'attributeid' => '5cc09402adccf621f802195b',
                    'name' => 'Array',
                    'description' => '',
                    'type' => 7,
                    'required' => false,
                    'disabled' => false,
                    'defaultvalue' => ['a', 'b', 'c', 'd'],
                    'predefinedvalues_total' => 0,
                ]
            ],
            'users' => [
                [
                    'name' => 'user1',
                    'read' => true,
                    'write' => true,
                ]
            ],
            'groups' => [
                [
                    'id' => '55557777bbbbccccddddaaaa',
                    'name' => 'EVERYONE',
                    'read' => true,
                    'write' => true,
                ]
            ],
            'paths' => [
                '/user1',
            ]
        ];
        
        return $args;
    }

    /**
     * Must be synced with the return of getValidArguments()
     * @return array
     */
    private function getValidApiRequest()
    {
        return [
            'id' => '5cc06b9fadccf621f80217d0',
            'name' => 'Review',
            'description' => 'Review description',
            'disabled' => 'false',
            'allowallpaths' => 'false',
            'type' => '3',
            'attribute0_attributeid' => '5cc06b9fadccf621f80217ce',
            'attribute0_name' => 'String',
            'attribute0_description' => 'String description',
            'attribute0_type' => '1',
            'attribute0_required' => 'true',
            'attribute0_disabled' => 'false',
            'attribute0_defaultvalue' => 'Default string',
            'attribute0_predefinedvalues_total' => '0',
            'attribute1_attributeid' => '5cc09402adccf621f8021956',
            'attribute1_name' => 'Int',
            'attribute1_description' => '',
            'attribute1_type' => '2',
            'attribute1_required' => 'true',
            'attribute1_disabled' => 'false',
            'attribute1_defaultvalue' => '1000',
            'attribute1_predefinedvalues_total' => '0',
            'attribute2_attributeid' => '5cc09402adccf621f8021957',
            'attribute2_name' => 'Dec',
            'attribute2_description' => '',
            'attribute2_type' => '3',
            'attribute2_required' => 'false',
            'attribute2_disabled' => 'false',
            'attribute2_defaultvalue' => '1.1',
            'attribute2_predefinedvalues_total' => '0',
            'attribute3_attributeid' => '5cc09402adccf621f8021958',
            'attribute3_name' => 'Bool',
            'attribute3_description' => '',
            'attribute3_type' => '4',
            'attribute3_required' => 'false',
            'attribute3_disabled' => 'false',
            'attribute3_defaultvalue' => 'true',
            'attribute3_predefinedvalues_total' => '0',
            'attribute4_attributeid' => '5cc09402adccf621f8021959',
            'attribute4_name' => 'Date',
            'attribute4_description' => '',
            'attribute4_type' => '5',
            'attribute4_required' => 'false',
            'attribute4_disabled' => 'false',
            'attribute4_defaultvalue' => '2019-04-03 00:00:00',
            'attribute4_predefinedvalues_total' => '0',
            'attribute5_attributeid' => '5cc09402adccf621f802195a',
            'attribute5_name' => 'Select',
            'attribute5_description' => '',
            'attribute5_type' => '6',
            'attribute5_required' => 'false',
            'attribute5_disabled' => 'false',
            'attribute5_defaultvalue' => 'A',
            'attribute5_predefinedvalue0' => 'A',
            'attribute5_predefinedvalue1' => 'B',
            'attribute5_predefinedvalue2' => 'C',
            'attribute5_predefinedvalues_total' => '3',
            'attribute6_attributeid' => '5cc09402adccf621f802195b',
            'attribute6_name' => 'Array',
            'attribute6_description' => '',
            'attribute6_type' => '7',
            'attribute6_required' => 'false',
            'attribute6_disabled' => 'false',
            'attribute6_defaultvalue' => 'a,b,c,d',
            'attribute6_predefinedvalues_total' => '0',
            'attributes_total' => '7',
            'user0_name' => 'user1',
            'user0_read' => 'true',
            'user0_write' => 'true',
            'users_total' => '1',
            'group0_id' => '55557777bbbbccccddddaaaa',
            'group0_name' => 'EVERYONE',
            'group0_read' => 'true',
            'group0_write' => 'true',
            'groups_total' => '1',
            'path0' => '/user1',
            'paths_total' => '1',
        ];
    }
}