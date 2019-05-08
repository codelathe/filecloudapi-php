<?php

namespace CodeLathe\FileCloudApi\Tests\CloudApi;

use codelathe\fccloudapi\AdminMetadataSetRecord;
use codelathe\fccloudapi\CloudAPI;
use codelathe\fccloudapi\MetadataSetRecord;
use CodeLathe\FileCloudApi\Tests\Fixtures\AccessibleCloudApi;
use PHPUnit\Framework\TestCase;

class GetMetadataForSearchTest extends TestCase
{
    public function testGetMetadataSetsForSearch()
    {
        $serverUrl = 'https://fcapi.example.com';
        $cloudApiMock = $this->getMockBuilder(AccessibleCloudApi::class)
            ->setConstructorArgs([$serverUrl])
            ->setMethods(['init', '__destruct', 'doPost'])
            ->getMock();

        $mockApiResponse = $this->getSampleResponse();

        $fullPath = '/tester';
        $cloudApiMock
            ->expects($this->any())
            ->method('doPost')
            ->with("$serverUrl/core/getmetadatasetsforsearch", http_build_query([
                'fullpath' => $fullPath,
            ]))
            ->willReturn($mockApiResponse);

        /** @var CloudAPI $cloudApiMock */
        $metadataSets = $cloudApiMock->getMetadataSetsForSearch($fullPath);

        $this->assertEquals(2, $metadataSets->getNumberOfRecords());

        foreach ($metadataSets->getRecords() as $i => $record) {
            $this->assertInstanceOf(MetadataSetRecord::class, $record);
        }
    }
    
    private function getSampleResponse()
    {
        return <<<RESPONSE
<metadatasets>
    <meta>
        <total>2</total>
    </meta>
    <metadataset>
        <id>5ccafe12adccf621f80342e6</id>
        <name>Sample4</name>
        <description>Description</description>
        <disabled></disabled>
        <read>1</read>
        <write>1</write>
        <attribute0_attributeid>5cc82c59adccf621f8023feb</attribute0_attributeid>
        <attribute0_name>Text</attribute0_name>
        <attribute0_description>Description</attribute0_description>
        <attribute0_type>1</attribute0_type>
        <attribute0_defaultvalue>Default</attribute0_defaultvalue>
        <attribute0_required></attribute0_required>
        <attribute0_disabled></attribute0_disabled>
        <attribute1_attributeid>5cc82c59adccf621f8023fec</attribute1_attributeid>
        <attribute1_name>Int</attribute1_name>
        <attribute1_description>Description</attribute1_description>
        <attribute1_type>2</attribute1_type>
        <attribute1_defaultvalue>100</attribute1_defaultvalue>
        <attribute1_required></attribute1_required>
        <attribute1_disabled></attribute1_disabled>
        <attribute2_attributeid>5cc82c59adccf621f8023fed</attribute2_attributeid>
        <attribute2_name>Dec</attribute2_name>
        <attribute2_description>Description</attribute2_description>
        <attribute2_type>3</attribute2_type>
        <attribute2_defaultvalue>1.1</attribute2_defaultvalue>
        <attribute2_required></attribute2_required>
        <attribute2_disabled></attribute2_disabled>
        <attribute3_attributeid>5cc82c59adccf621f8023fee</attribute3_attributeid>
        <attribute3_name>Bool</attribute3_name>
        <attribute3_description>Description</attribute3_description>
        <attribute3_type>4</attribute3_type>
        <attribute3_defaultvalue>false</attribute3_defaultvalue>
        <attribute3_required></attribute3_required>
        <attribute3_disabled></attribute3_disabled>
        <attribute4_attributeid>5cc82c59adccf621f8023fef</attribute4_attributeid>
        <attribute4_name>Date</attribute4_name>
        <attribute4_description>Description</attribute4_description>
        <attribute4_type>5</attribute4_type>
        <attribute4_defaultvalue>2019-04-03 00:00:00</attribute4_defaultvalue>
        <attribute4_required></attribute4_required>
        <attribute4_disabled></attribute4_disabled>
        <attribute5_attributeid>5cc82c59adccf621f8023ff0</attribute5_attributeid>
        <attribute5_name>Select</attribute5_name>
        <attribute5_description>Description</attribute5_description>
        <attribute5_type>6</attribute5_type>
        <attribute5_defaultvalue>foo</attribute5_defaultvalue>
        <attribute5_required></attribute5_required>
        <attribute5_disabled></attribute5_disabled>
        <attribute5_predefinedvalue0>foo</attribute5_predefinedvalue0>
        <attribute5_predefinedvalue1>bar</attribute5_predefinedvalue1>
        <attribute5_predefinedvalue2>baz</attribute5_predefinedvalue2>
        <attribute5_predefinedvalues_total>3</attribute5_predefinedvalues_total>
        <attribute6_attributeid>5cc82c59adccf621f8023ff1</attribute6_attributeid>
        <attribute6_name>MultiSelect</attribute6_name>
        <attribute6_description>Description</attribute6_description>
        <attribute6_type>7</attribute6_type>
        <attribute6_defaultvalue>a,b,c,d</attribute6_defaultvalue>
        <attribute6_required></attribute6_required>
        <attribute6_disabled></attribute6_disabled>
        <attributes_total>7</attributes_total>
    </metadataset>
    <metadataset>
        <id>55557777bbbbccccddddaaab</id>
        <name>Image metadata</name>
        <description>Image metadata (EXIF)</description>
        <disabled></disabled>
        <read>1</read>
        <write></write>
        <attribute0_attributeid>5cd02a81adccf61c6c004ce0</attribute0_attributeid>
        <attribute0_name>Width</attribute0_name>
        <attribute0_description>Image Width in Pixels</attribute0_description>
        <attribute0_type>2</attribute0_type>
        <attribute0_defaultvalue></attribute0_defaultvalue>
        <attribute0_required></attribute0_required>
        <attribute0_disabled></attribute0_disabled>
        <attribute1_attributeid>5cd02a81adccf61c6c004ce1</attribute1_attributeid>
        <attribute1_name>Height</attribute1_name>
        <attribute1_description>Image Height in Pixels</attribute1_description>
        <attribute1_type>2</attribute1_type>
        <attribute1_defaultvalue></attribute1_defaultvalue>
        <attribute1_required></attribute1_required>
        <attribute1_disabled></attribute1_disabled>
        <attribute2_attributeid>5cd02a81adccf61c6c004ce2</attribute2_attributeid>
        <attribute2_name>Image Orientation</attribute2_name>
        <attribute2_description>Image orientation</attribute2_description>
        <attribute2_type>6</attribute2_type>
        <attribute2_defaultvalue></attribute2_defaultvalue>
        <attribute2_required></attribute2_required>
        <attribute2_disabled></attribute2_disabled>
        <attribute2_predefinedvalue0>Horizontal</attribute2_predefinedvalue0>
        <attribute2_predefinedvalue1>Vertical</attribute2_predefinedvalue1>
        <attribute2_predefinedvalues_total>2</attribute2_predefinedvalues_total>
        <attribute3_attributeid>5cd02a81adccf61c6c004ce3</attribute3_attributeid>
        <attribute3_name>Image Orientation - Numeric</attribute3_name>
        <attribute3_description>Image orientation as a number (8 different orientations)</attribute3_description>
        <attribute3_type>2</attribute3_type>
        <attribute3_defaultvalue></attribute3_defaultvalue>
        <attribute3_required></attribute3_required>
        <attribute3_disabled></attribute3_disabled>
        <attribute4_attributeid>5cd02a81adccf61c6c004ce4</attribute4_attributeid>
        <attribute4_name>Image XResolution</attribute4_name>
        <attribute4_description>Image Resolution in width direction</attribute4_description>
        <attribute4_type>1</attribute4_type>
        <attribute4_defaultvalue></attribute4_defaultvalue>
        <attribute4_required></attribute4_required>
        <attribute4_disabled></attribute4_disabled>
        <attribute5_attributeid>5cd02a81adccf61c6c004ce5</attribute5_attributeid>
        <attribute5_name>Image YResolution</attribute5_name>
        <attribute5_description>Image Resolution in height direction</attribute5_description>
        <attribute5_type>1</attribute5_type>
        <attribute5_defaultvalue></attribute5_defaultvalue>
        <attribute5_required></attribute5_required>
        <attribute5_disabled></attribute5_disabled>
        <attribute6_attributeid>5cd02a81adccf61c6c004ce6</attribute6_attributeid>
        <attribute6_name>Unit of Resolution</attribute6_name>
        <attribute6_description>Unit of resolution</attribute6_description>
        <attribute6_type>6</attribute6_type>
        <attribute6_defaultvalue></attribute6_defaultvalue>
        <attribute6_required></attribute6_required>
        <attribute6_disabled></attribute6_disabled>
        <attribute6_predefinedvalue0>NaN</attribute6_predefinedvalue0>
        <attribute6_predefinedvalue1>in</attribute6_predefinedvalue1>
        <attribute6_predefinedvalue2>cm</attribute6_predefinedvalue2>
        <attribute6_predefinedvalues_total>3</attribute6_predefinedvalues_total>
        <attribute7_attributeid>5cd02a81adccf61c6c004ce7</attribute7_attributeid>
        <attribute7_name>Image Creation Date</attribute7_name>
        <attribute7_description>Date of image creation. Date when photo was actually taken.</attribute7_description>
        <attribute7_type>5</attribute7_type>
        <attribute7_defaultvalue></attribute7_defaultvalue>
        <attribute7_required></attribute7_required>
        <attribute7_disabled></attribute7_disabled>
        <attribute8_attributeid>5cd02a81adccf61c6c004ce8</attribute8_attributeid>
        <attribute8_name>Make</attribute8_name>
        <attribute8_description>Camera Make (manufacturer)</attribute8_description>
        <attribute8_type>1</attribute8_type>
        <attribute8_defaultvalue></attribute8_defaultvalue>
        <attribute8_required></attribute8_required>
        <attribute8_disabled></attribute8_disabled>
        <attribute9_attributeid>5cd02a81adccf61c6c004ce9</attribute9_attributeid>
        <attribute9_name>Model</attribute9_name>
        <attribute9_description>Camera Model</attribute9_description>
        <attribute9_type>1</attribute9_type>
        <attribute9_defaultvalue></attribute9_defaultvalue>
        <attribute9_required></attribute9_required>
        <attribute9_disabled></attribute9_disabled>
        <attribute10_attributeid>5cd02a81adccf61c6c004cea</attribute10_attributeid>
        <attribute10_name>Artist</attribute10_name>
        <attribute10_description>Artist</attribute10_description>
        <attribute10_type>1</attribute10_type>
        <attribute10_defaultvalue></attribute10_defaultvalue>
        <attribute10_required></attribute10_required>
        <attribute10_disabled></attribute10_disabled>
        <attribute11_attributeid>5cd02a81adccf61c6c004ceb</attribute11_attributeid>
        <attribute11_name>Copyright</attribute11_name>
        <attribute11_description>Copyright</attribute11_description>
        <attribute11_type>1</attribute11_type>
        <attribute11_defaultvalue></attribute11_defaultvalue>
        <attribute11_required></attribute11_required>
        <attribute11_disabled></attribute11_disabled>
        <attribute12_attributeid>5cd02a81adccf61c6c004cec</attribute12_attributeid>
        <attribute12_name>Software</attribute12_name>
        <attribute12_description>Software used for the last image edit</attribute12_description>
        <attribute12_type>1</attribute12_type>
        <attribute12_defaultvalue></attribute12_defaultvalue>
        <attribute12_required></attribute12_required>
        <attribute12_disabled></attribute12_disabled>
        <attribute13_attributeid>5cd02a81adccf61c6c004ced</attribute13_attributeid>
        <attribute13_name>Exposure Time</attribute13_name>
        <attribute13_description>Copyright</attribute13_description>
        <attribute13_type>1</attribute13_type>
        <attribute13_defaultvalue></attribute13_defaultvalue>
        <attribute13_required></attribute13_required>
        <attribute13_disabled></attribute13_disabled>
        <attribute14_attributeid>5cd02a81adccf61c6c004cee</attribute14_attributeid>
        <attribute14_name>FNumber</attribute14_name>
        <attribute14_description>FNumber</attribute14_description>
        <attribute14_type>1</attribute14_type>
        <attribute14_defaultvalue></attribute14_defaultvalue>
        <attribute14_required></attribute14_required>
        <attribute14_disabled></attribute14_disabled>
        <attribute15_attributeid>5cd02a81adccf61c6c004cef</attribute15_attributeid>
        <attribute15_name>ISO</attribute15_name>
        <attribute15_description>ISO</attribute15_description>
        <attribute15_type>1</attribute15_type>
        <attribute15_defaultvalue></attribute15_defaultvalue>
        <attribute15_required></attribute15_required>
        <attribute15_disabled></attribute15_disabled>
        <attribute16_attributeid>5cd02a81adccf61c6c004cf0</attribute16_attributeid>
        <attribute16_name>Exif Version</attribute16_name>
        <attribute16_description>Exif version used</attribute16_description>
        <attribute16_type>1</attribute16_type>
        <attribute16_defaultvalue></attribute16_defaultvalue>
        <attribute16_required></attribute16_required>
        <attribute16_disabled></attribute16_disabled>
        <attribute17_attributeid>5cd02a81adccf61c6c004cf1</attribute17_attributeid>
        <attribute17_name>Shutter Speed</attribute17_name>
        <attribute17_description>The shutter speed</attribute17_description>
        <attribute17_type>1</attribute17_type>
        <attribute17_defaultvalue></attribute17_defaultvalue>
        <attribute17_required></attribute17_required>
        <attribute17_disabled></attribute17_disabled>
        <attribute18_attributeid>5cd02a81adccf61c6c004cf2</attribute18_attributeid>
        <attribute18_name>Aperture</attribute18_name>
        <attribute18_description>Aperture values</attribute18_description>
        <attribute18_type>1</attribute18_type>
        <attribute18_defaultvalue></attribute18_defaultvalue>
        <attribute18_required></attribute18_required>
        <attribute18_disabled></attribute18_disabled>
        <attribute19_attributeid>5cd02a81adccf61c6c004cf3</attribute19_attributeid>
        <attribute19_name>Brightness</attribute19_name>
        <attribute19_description>Brightness</attribute19_description>
        <attribute19_type>1</attribute19_type>
        <attribute19_defaultvalue></attribute19_defaultvalue>
        <attribute19_required></attribute19_required>
        <attribute19_disabled></attribute19_disabled>
        <attribute20_attributeid>5cd02a81adccf61c6c004cf4</attribute20_attributeid>
        <attribute20_name>Focal Length</attribute20_name>
        <attribute20_description>Focal Length</attribute20_description>
        <attribute20_type>1</attribute20_type>
        <attribute20_defaultvalue></attribute20_defaultvalue>
        <attribute20_required></attribute20_required>
        <attribute20_disabled></attribute20_disabled>
        <attributes_total>21</attributes_total>
    </metadataset>
</metadatasets>
RESPONSE;

    }
}