<?php

namespace CodeLathe\FileCloudApi\Tests\CloudApi;

use codelathe\fccloudapi\CloudAPI;
use codelathe\fccloudapi\CommandRecord;
use CodeLathe\FileCloudApi\Tests\Fixtures\AccessibleCloudApi;
use PHPUnit\Framework\TestCase;

class SaveAttributeValuesTest extends TestCase
{
    /**
     * Test whether it returns the correct data record for
     * a successful API request.
     */
    public function testOnSuccess()
    {
        $serverUrl = 'https://fcapi.example.com';
        $cloudApiMock = $this->getMockBuilder(AccessibleCloudApi::class)
            ->setConstructorArgs([$serverUrl])
            ->setMethods(['init', '__destruct', 'doPost'])
            ->getMock();

        $mockApiResponse = <<<RESPONSE
<commands>
    <command>
        <type>saveattributevalues</type>
        <result>1</result>
        <message>Attribute values saved successfully (setId: 5ccafe12adccf621f80342e6 | fullpath: /tester/textfile1.txt)!</message>
    </command>
</commands>
RESPONSE;
        $mockApiRequest = $this->getValidApiRequest();

        $cloudApiMock->method('doPost')
            ->with("{$serverUrl}/core/saveattributevalues", http_build_query($mockApiRequest))
            ->willReturn($mockApiResponse);

        /** @var CloudAPI $cloudApiMock */
        /** @var CommandRecord $commandRecord */
        $commandRecord = $cloudApiMock->saveAttributeValues(...array_values($this->getWrapperArguments()));
        $this->assertEquals(1, $commandRecord->getResult());
        $this->assertEquals('saveattributevalues', $commandRecord->getType());
        $this->assertEquals('Attribute values saved successfully (setId: 5ccafe12adccf621f80342e6 | fullpath: /tester/textfile1.txt)!', $commandRecord->getMessage());
    }

    /**
     * Test whether it returns the correct data record for
     * an unsuccessful API request.
     */
    public function testInvalidSetId()
    {
        $serverUrl = 'https://fcapi.example.com';
        $cloudApiMock = $this->getMockBuilder(AccessibleCloudApi::class)
            ->setConstructorArgs([$serverUrl])
            ->setMethods(['init', '__destruct', 'doPost'])
            ->getMock();

        $mockApiResponse = <<<RESPONSE
<commands>
    <command>
        <type>saveattributevalues</type>
        <result>0</result>
        <message>Failed to save attribute values. Reason: Incorrect set id provided</message>
    </command>
</commands>
RESPONSE;
        $mockApiRequest = $this->getValidApiRequest();
        $mockApiRequest['setid'] = '5ccafe12adccf621f80342e7';  // Pretend this is invalid/dne


        $cloudApiMock->method('doPost')
            ->with("{$serverUrl}/core/saveattributevalues", http_build_query($mockApiRequest))
            ->willReturn($mockApiResponse);

        $arguments = $this->getWrapperArguments();
        $arguments['setid'] = '5ccafe12adccf621f80342e7';   // Pretend this is invalid/dne

        /** @var CloudAPI $cloudApiMock */
        /** @var CommandRecord $commandRecord */
        $commandRecord = $cloudApiMock->saveAttributeValues(...array_values($arguments));
        $this->assertEquals(0, $commandRecord->getResult());
        $this->assertEquals('saveattributevalues', $commandRecord->getType());
        $this->assertEquals(
            'Failed to save attribute values. Reason: Incorrect set id provided',
            $commandRecord->getMessage()
        );
    }

    private function getWrapperArguments()
    {
        return [
            'fullpath' => '/tester/textfile1.txt',
            'setid' => '5ccafe12adccf621f80342e6',
            'attributes' => [
                0 => [
                    'attributeid' => '5ccafe12adccf621f80342df',
                    'value' => 'Default',
                ],
                1 => [
                    'attributeid' => '5ccafe12adccf621f80342e0',
                    'value' => '100',
                ],
                2 => [
                    'attributeid' => '5ccafe12adccf621f80342e1',
                    'value' => '1.1',
                ],
                3 => [
                    'attributeid' => '5ccafe12adccf621f80342e2',
                    'value' => 'false',
                ],
                4 => [
                    'attributeid' => '5ccafe12adccf621f80342e3',
                    'value' => '2019-04-03 00:00:00',
                ],
                5 => [
                    'attributeid' => '5ccafe12adccf621f80342e4',
                    'value' => 'foo',
                ],
                6 => [
                    'attributeid' => '5ccafe12adccf621f80342e5',
                    'value' => 'a,b,c,d',
                ],
            ]
        ];
    }
    
    /**
     * 
     */
    private function getValidApiRequest()
    {
        return [
            'fullpath' => '/tester/textfile1.txt',
            'setid' => '5ccafe12adccf621f80342e6',
            'attribute0_attributeid' => '5ccafe12adccf621f80342df',
            'attribute0_value' => 'Default',
            'attribute1_attributeid' => '5ccafe12adccf621f80342e0',
            'attribute1_value' => '100',
            'attribute2_attributeid' => '5ccafe12adccf621f80342e1',
            'attribute2_value' => '1.1',
            'attribute3_attributeid' => '5ccafe12adccf621f80342e2',
            'attribute3_value' => 'false',
            'attribute4_attributeid' => '5ccafe12adccf621f80342e3',
            'attribute4_value' => '2019-04-03 00:00:00',
            'attribute5_attributeid' => '5ccafe12adccf621f80342e4',
            'attribute5_value' => 'foo',
            'attribute6_attributeid' => '5ccafe12adccf621f80342e5',
            'attribute6_value' => 'a,b,c,d',
            'attributes_total' => '7'
        ];
    }
}