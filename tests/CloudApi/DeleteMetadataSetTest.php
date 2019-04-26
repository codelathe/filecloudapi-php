<?php

namespace CodeLathe\FileCloudApi\Tests\CloudApi;

use codelathe\fccloudapi\CloudAdminAPI;
use codelathe\fccloudapi\CommandRecord;
use CodeLathe\FileCloudApi\Tests\Fixtures\AccessibleCloudAdminApi;
use PHPUnit\Framework\TestCase;

class DeleteMetadataSetTest extends TestCase
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
        <type>deletemetadataset</type>
        <result>1</result>
        <message>Metadata set definition (setId: 5cc06b9fadccf621f80217d0) deleted successfully</message>
    </command>
</commands>
RESPONSE;

        $validSetId = '5cc06b9fadccf621f80217d0';
        $cloudApiMock->method('doPost')
            ->with("{$serverUrl}/admin/deletemetadataset", http_build_query(['setid' => $validSetId]))
            ->willReturn($mockApiResponse);

        /** @var CloudAdminAPI $cloudApiMock */
        /** @var CommandRecord $commandRecord */
        $commandRecord = $cloudApiMock->deleteMetadataSet($validSetId);
        $this->assertEquals(1, $commandRecord->getResult());
        $this->assertEquals('deletemetadataset', $commandRecord->getType());
        $this->assertEquals('Metadata set definition (setId: 5cc06b9fadccf621f80217d0) deleted successfully', $commandRecord->getMessage());
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
        <type>deletemetadataset</type>
        <result>0</result>
        <message>Failed to delete the given Metadata Set. Reason: No Set Definition for a given id exists in the DB.</message>
    </command>
</commands>
RESPONSE;

        $invalidSetId = '5cc1c9c3adccf621f8021b06';
        $cloudApiMock->method('doPost')
            ->with("{$serverUrl}/admin/deletemetadataset", http_build_query(['setid' => $invalidSetId]))
            ->willReturn($mockApiResponse);
        
        /** @var CloudAdminAPI $cloudApiMock */
        /** @var CommandRecord $commandRecord */
        $commandRecord = $cloudApiMock->deleteMetadataSet($invalidSetId);
        $this->assertEquals(0, $commandRecord->getResult());
        $this->assertEquals('deletemetadataset', $commandRecord->getType());
        $this->assertEquals(
            'Failed to delete the given Metadata Set. Reason: No Set Definition for a given id exists in the DB.',
            $commandRecord->getMessage()
        );
    }
}