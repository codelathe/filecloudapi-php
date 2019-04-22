<?php

namespace CodeLathe\FileCloudApi\Tests\CloudApi;

use codelathe\fccloudapi\CloudAPI;
use CodeLathe\FileCloudApi\MetadataValueRecord;
use CodeLathe\FileCloudApi\Tests\Fixtures\AccessibleCloudApi;
use PHPUnit\Framework\TestCase;

class MetadataValueTest extends TestCase
{
    public function testCanGetMetadataValues()
    {
        $serverUrl = 'https://fcapi.example.com';
        $cloudApiMock = $this->getMockBuilder(AccessibleCloudApi::class)
            ->setConstructorArgs([$serverUrl])
            ->setMethods(['init', '__destruct', 'doPost'])
            ->getMock();

        $cloudApiMock->method('doPost')
             ->willReturn($this->getSampleResponse());

        /** @var CloudAPI $cloudApiMock */
        $metadataSets = $cloudApiMock->getMetadataValues('/tester/textfile1.txt');
        
        $this->assertEquals(3, $metadataSets->getNumberOfRecords());
        foreach ($metadataSets->getRecords() as $i => $record) {
            $this->assertInstanceOf(MetadataValueRecord::class, $record);
        }
    }
    
    private function getSampleResponse()
    {
        return <<<RESPONSE
<metadatavalues>
    <meta>
        <total>3</total>
    </meta>
    <metadatasetvalue>
        <id>55557777bbbbccccddddaaaa</id>
        <name>Default</name>
        <description>Default metadata set definition will be automatically bound to every single File and Folder.</description>
        <settype>2</settype>
        <read>1</read>
        <write>1</write>
        <attribute0_attributeid>5cb729e7adccf621f80147e7</attribute0_attributeid>
        <attribute0_name>Tags</attribute0_name>
        <attribute0_description>Tags</attribute0_description>
        <attribute0_disabled></attribute0_disabled>
        <attribute0_required></attribute0_required>
        <attribute0_datatype>7</attribute0_datatype>
        <attribute0_value></attribute0_value>
        <attribute1_attributeid>5cb7387fadccf621f8014907</attribute1_attributeid>
        <attribute1_name>Title</attribute1_name>
        <attribute1_description>Title if any</attribute1_description>
        <attribute1_disabled></attribute1_disabled>
        <attribute1_required>1</attribute1_required>
        <attribute1_datatype>1</attribute1_datatype>
        <attribute1_value>Untitled</attribute1_value>
        <attribute2_attributeid>5cb7387fadccf621f8014908</attribute2_attributeid>
        <attribute2_name>Duration</attribute2_name>
        <attribute2_description>Duration of material in minutes</attribute2_description>
        <attribute2_disabled></attribute2_disabled>
        <attribute2_required>1</attribute2_required>
        <attribute2_datatype>2</attribute2_datatype>
        <attribute2_value>120</attribute2_value>
        <attribute3_attributeid>5cb7387fadccf621f8014909</attribute3_attributeid>
        <attribute3_name>Rating</attribute3_name>
        <attribute3_description>1-10</attribute3_description>
        <attribute3_disabled></attribute3_disabled>
        <attribute3_required>1</attribute3_required>
        <attribute3_datatype>3</attribute3_datatype>
        <attribute3_value>7.5</attribute3_value>
        <attribute4_attributeid>5cb7387fadccf621f801490a</attribute4_attributeid>
        <attribute4_name>Rated 18</attribute4_name>
        <attribute4_description></attribute4_description>
        <attribute4_disabled></attribute4_disabled>
        <attribute4_required>1</attribute4_required>
        <attribute4_datatype>4</attribute4_datatype>
        <attribute4_value>true</attribute4_value>
        <attribute5_attributeid>5cb7387fadccf621f801490b</attribute5_attributeid>
        <attribute5_name>Release date</attribute5_name>
        <attribute5_description></attribute5_description>
        <attribute5_disabled></attribute5_disabled>
        <attribute5_required>1</attribute5_required>
        <attribute5_datatype>5</attribute5_datatype>
        <attribute5_value>1985-07-10 00:00:00</attribute5_value>
        <attribute6_attributeid>5cb7387fadccf621f801490c</attribute6_attributeid>
        <attribute6_name>Color</attribute6_name>
        <attribute6_description></attribute6_description>
        <attribute6_disabled></attribute6_disabled>
        <attribute6_required>1</attribute6_required>
        <attribute6_datatype>6</attribute6_datatype>
        <attribute6_value>Red</attribute6_value>
        <attribute6_enumvalues>Red,Orange,Yellow,Green,Blue,Indigo,Violet</attribute6_enumvalues>
        <attributes_total>7</attributes_total>
    </metadatasetvalue>
    <metadatasetvalue>
        <id>55557777bbbbccccddddaaac</id>
        <name>Document Life Cycle metadata</name>
        <description>Stores information regarding document life cycle</description>
        <settype>1</settype>
        <read>1</read>
        <write></write>
        <attribute0_attributeid>5cb729e7adccf621f8014813</attribute0_attributeid>
        <attribute0_name>Creation Date</attribute0_name>
        <attribute0_description>File/Folder creation date</attribute0_description>
        <attribute0_disabled></attribute0_disabled>
        <attribute0_required></attribute0_required>
        <attribute0_datatype>5</attribute0_datatype>
        <attribute0_value>2019-04-17 21:28:12</attribute0_value>
        <attribute1_attributeid>5cb729e7adccf621f8014814</attribute1_attributeid>
        <attribute1_name>Last Access</attribute1_name>
        <attribute1_description>Last access date</attribute1_description>
        <attribute1_disabled></attribute1_disabled>
        <attribute1_required></attribute1_required>
        <attribute1_datatype>5</attribute1_datatype>
        <attribute1_value>2019-04-17 21:28:12</attribute1_value>
        <attribute2_attributeid>5cb729e7adccf621f8014815</attribute2_attributeid>
        <attribute2_name>Last Modification</attribute2_name>
        <attribute2_description>Last modification date</attribute2_description>
        <attribute2_disabled></attribute2_disabled>
        <attribute2_required></attribute2_required>
        <attribute2_datatype>5</attribute2_datatype>
        <attribute2_value>2019-04-17 21:28:12</attribute2_value>
        <attribute3_attributeid>5cb729e7adccf621f8014816</attribute3_attributeid>
        <attribute3_name>Check Sum</attribute3_name>
        <attribute3_description>File SHA256 fingerprint</attribute3_description>
        <attribute3_disabled></attribute3_disabled>
        <attribute3_required></attribute3_required>
        <attribute3_datatype>1</attribute3_datatype>
        <attribute3_value>38cbff61976ae5d28db5f38f5c6476c9f869d2bb674ce1af239b61de1ca50176</attribute3_value>
        <attributes_total>4</attributes_total>
    </metadatasetvalue>
    <metadatasetvalue>
        <id>5cb73b04adccf621f8014968</id>
        <name>Readable</name>
        <description>Readable files metadataset</description>
        <settype>3</settype>
        <read>1</read>
        <write>1</write>
        <attribute0_attributeid>5cb73b04adccf621f8014961</attribute0_attributeid>
        <attribute0_name>Summary</attribute0_name>
        <attribute0_description>A short summary of this readable</attribute0_description>
        <attribute0_disabled></attribute0_disabled>
        <attribute0_required>1</attribute0_required>
        <attribute0_datatype>1</attribute0_datatype>
        <attribute0_value>Unsummarized</attribute0_value>
        <attribute1_attributeid>5cb73b04adccf621f8014962</attribute1_attributeid>
        <attribute1_name>Pages</attribute1_name>
        <attribute1_description></attribute1_description>
        <attribute1_disabled></attribute1_disabled>
        <attribute1_required>1</attribute1_required>
        <attribute1_datatype>2</attribute1_datatype>
        <attribute1_value>1192</attribute1_value>
        <attribute2_attributeid>5cb73b04adccf621f8014963</attribute2_attributeid>
        <attribute2_name>Size</attribute2_name>
        <attribute2_description>File size in MB</attribute2_description>
        <attribute2_disabled></attribute2_disabled>
        <attribute2_required>1</attribute2_required>
        <attribute2_datatype>3</attribute2_datatype>
        <attribute2_value>5.5</attribute2_value>
        <attribute3_attributeid>5cb73b04adccf621f8014964</attribute3_attributeid>
        <attribute3_name>English</attribute3_name>
        <attribute3_description></attribute3_description>
        <attribute3_disabled></attribute3_disabled>
        <attribute3_required></attribute3_required>
        <attribute3_datatype>4</attribute3_datatype>
        <attribute3_value>false</attribute3_value>
        <attribute4_attributeid>5cb73b04adccf621f8014965</attribute4_attributeid>
        <attribute4_name>Publish date</attribute4_name>
        <attribute4_description></attribute4_description>
        <attribute4_disabled></attribute4_disabled>
        <attribute4_required>1</attribute4_required>
        <attribute4_datatype>5</attribute4_datatype>
        <attribute4_value>1987-07-15 00:00:00</attribute4_value>
        <attribute5_attributeid>5cb73b04adccf621f8014966</attribute5_attributeid>
        <attribute5_name>Format</attribute5_name>
        <attribute5_description></attribute5_description>
        <attribute5_disabled></attribute5_disabled>
        <attribute5_required></attribute5_required>
        <attribute5_datatype>6</attribute5_datatype>
        <attribute5_value>PDF</attribute5_value>
        <attribute5_enumvalues>PDF,Kindle,Text,Markdown</attribute5_enumvalues>
        <attribute6_attributeid>5cb73b04adccf621f8014967</attribute6_attributeid>
        <attribute6_name>Tags</attribute6_name>
        <attribute6_description></attribute6_description>
        <attribute6_disabled></attribute6_disabled>
        <attribute6_required></attribute6_required>
        <attribute6_datatype>7</attribute6_datatype>
        <attribute6_value>Self-help,Philosophy</attribute6_value>
        <attributes_total>7</attributes_total>
    </metadatasetvalue>
</metadatavalues>
RESPONSE;

    }
}