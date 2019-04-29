<?php

namespace CodeLathe\FileCloudApi\Tests\CloudApi;

use codelathe\fccloudapi\AdminMetadataSetRecord;
use codelathe\fccloudapi\CloudAdminAPI;
use CodeLathe\FileCloudApi\Tests\Fixtures\AccessibleCloudAdminApi;
use PHPUnit\Framework\TestCase;

class GetMetadataTest extends TestCase
{
    public function testGetMetadataSet()
    {
        $serverUrl = 'https://fcapi.example.com';
        $cloudApiMock = $this->getMockBuilder(AccessibleCloudAdminApi::class)
            ->setConstructorArgs([$serverUrl])
            ->setMethods(['init', '__destruct', 'doPost'])
            ->getMock();

        $mockApiResponse = $this->getSampleResponse();

        $setId = '55557777bbbbccccddddaaac';
        $cloudApiMock
            ->expects($this->any())
            ->method('doPost')
            ->with("$serverUrl/admin/getmetadataset", http_build_query([
                'setId' => $setId,
            ]))
            ->willReturn($mockApiResponse);

        /** @var CloudAdminAPI $cloudApiMock */
        $metadataSet = $cloudApiMock->getMetadataSet($setId);

        $this->assertInstanceOf(AdminMetadataSetRecord::class, $metadataSet);
        $this->assertSame($setId, $metadataSet->getId());
    }
    
    public function testGetNull()
    {
        $serverUrl = 'https://fcapi.example.com';
        $cloudApiMock = $this->getMockBuilder(AccessibleCloudAdminApi::class)
            ->setConstructorArgs([$serverUrl])
            ->setMethods(['init', '__destruct', 'doPost'])
            ->getMock();

        // Api responds with blank when invalid id is supplied
        $mockApiResponse = '';

        $setId = 'aNonExistentId';
        $cloudApiMock
            ->expects($this->any())
            ->method('doPost')
            ->with("$serverUrl/admin/getmetadataset", http_build_query([
                'setId' => $setId,
            ]))
            ->willReturn($mockApiResponse);

        /** @var CloudAdminAPI $cloudApiMock */
        $metadataSet = $cloudApiMock->getMetadataSet($setId);

        $this->assertNull($metadataSet);
    }
    
    public function testReturnsNullWhenNoDefaultValueForDate()
    {
        $serverUrl = 'https://fcapi.example.com';
        $cloudApiMock = $this->getMockBuilder(AccessibleCloudAdminApi::class)
            ->setConstructorArgs([$serverUrl])
            ->setMethods(['init', '__destruct', 'doPost'])
            ->getMock();

        $mockApiResponse = $this->getSampleResponse();

        $setId = 'aNonExistentId';
        $cloudApiMock
            ->expects($this->any())
            ->method('doPost')
            ->with("$serverUrl/admin/getmetadataset", http_build_query([
                'setId' => $setId,
            ]))
            ->willReturn($mockApiResponse);

        /** @var CloudAdminAPI $cloudApiMock */
        $metadataSet = $cloudApiMock->getMetadataSet($setId);

        $this->assertNull($metadataSet->getAttributes()[0]['defaultvalue']);
    }
    
    private function getSampleResponse()
    {
        return <<<RESPONSE
<metadataset>
    <id>55557777bbbbccccddddaaac</id>
    <name>Document Life Cycle metadata</name>
    <description>Stores information regarding document life cycle</description>
    <type>1</type>
    <disabled></disabled>
    <allowallpaths>1</allowallpaths>
    <attribute0_attributeid>5cc34d98adccf621f80220c3</attribute0_attributeid>
    <attribute0_name>Creation Date</attribute0_name>
    <attribute0_description>File/Folder creation date</attribute0_description>
    <attribute0_type>5</attribute0_type>
    <attribute0_defaultvalue></attribute0_defaultvalue>
    <attribute0_required></attribute0_required>
    <attribute0_disabled></attribute0_disabled>
    <attribute1_attributeid>5cc34d98adccf621f80220c4</attribute1_attributeid>
    <attribute1_name>Last Access</attribute1_name>
    <attribute1_description>Last access date</attribute1_description>
    <attribute1_type>5</attribute1_type>
    <attribute1_defaultvalue></attribute1_defaultvalue>
    <attribute1_required></attribute1_required>
    <attribute1_disabled></attribute1_disabled>
    <attribute2_attributeid>5cc34d98adccf621f80220c5</attribute2_attributeid>
    <attribute2_name>Last Modification</attribute2_name>
    <attribute2_description>Last modification date</attribute2_description>
    <attribute2_type>5</attribute2_type>
    <attribute2_defaultvalue></attribute2_defaultvalue>
    <attribute2_required></attribute2_required>
    <attribute2_disabled></attribute2_disabled>
    <attribute3_attributeid>5cc34d98adccf621f80220c6</attribute3_attributeid>
    <attribute3_name>Check Sum</attribute3_name>
    <attribute3_description>File SHA256 fingerprint</attribute3_description>
    <attribute3_type>1</attribute3_type>
    <attribute3_defaultvalue></attribute3_defaultvalue>
    <attribute3_required></attribute3_required>
    <attribute3_disabled></attribute3_disabled>
    <attributes_total>4</attributes_total>
    <users_total>0</users_total>
    <group0_id>55557777bbbbccccddddaaaa</group0_id>
    <group0_name>EVERYONE</group0_name>
    <group0_read>1</group0_read>
    <group0_write></group0_write>
    <groups_total>1</groups_total>
    <paths_total>0</paths_total>
</metadataset>
RESPONSE;

    }
}