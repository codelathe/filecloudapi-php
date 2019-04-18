<?php

/*******************************************************************************
 * Copyright (c) 2019 CodeLathe LLC. All rights Reserved.
 * This file is part of FileCloud  http://www.getfilecloud.com
 *******************************************************************************/

namespace CodeLathe\FileCloudApi\Tests\CloudApi;

use codelathe\fccloudapi\CloudAPI;
use CodeLathe\FileCloudApi\MetadataSetRecord;
use CodeLathe\FileCloudApi\Tests\Fixtures\AccessibleCloudApi;
use PHPUnit\Framework\TestCase;

class AvailableMetadataSetTest extends TestCase
{
    public function testCanGetAvailableMetadataSets()
    {
        $serverUrl = 'https://fcapi.example.com';
        $cloudApiMock = $this->getMockBuilder(AccessibleCloudApi::class)
            ->setConstructorArgs([$serverUrl])
            ->setMethods(['init', '__destruct', 'doPost'])
            ->getMock();

        $mockApiResponse = $this->getSampleResponse();

        $cloudApiMock->method('doPost')
            ->willReturn($mockApiResponse);

        /** @var CloudAPI $cloudApiMock */
        $metadataSets = $cloudApiMock->getAvailableMetadataSets('/tester/textfile1.txt');

        $this->assertEquals(1, $metadataSets->getNumberOfRecords());

        foreach ($metadataSets->getRecords() as $i => $record) {
            $this->assertInstanceOf(MetadataSetRecord::class, $record);
        }
    }

    /**
     * @return string
     */
    private function getSampleResponse(): string
    {
        return <<<RESPONSE
<metadatasets>
    <meta>
        <total>1</total>
    </meta>
    <metadataset>
        <id>5cb73b04adccf621f8014968</id>
        <name>Readable</name>
        <description>Readable files metadataset</description>
        <disabled></disabled>
        <read>1</read>
        <write>1</write>
        <attribute0_attributeid>5cb73b04adccf621f8014961</attribute0_attributeid>
        <attribute0_name>Summary</attribute0_name>
        <attribute0_description>A short summary of this readable</attribute0_description>
        <attribute0_type>1</attribute0_type>
        <attribute0_defaultvalue>Unsummarized</attribute0_defaultvalue>
        <attribute0_required>1</attribute0_required>
        <attribute0_disabled></attribute0_disabled>
        <attribute1_attributeid>5cb73b04adccf621f8014962</attribute1_attributeid>
        <attribute1_name>Pages</attribute1_name>
        <attribute1_description></attribute1_description>
        <attribute1_type>2</attribute1_type>
        <attribute1_defaultvalue>1192</attribute1_defaultvalue>
        <attribute1_required>1</attribute1_required>
        <attribute1_disabled></attribute1_disabled>
        <attribute2_attributeid>5cb73b04adccf621f8014963</attribute2_attributeid>
        <attribute2_name>Size</attribute2_name>
        <attribute2_description>File size in MB</attribute2_description>
        <attribute2_type>3</attribute2_type>
        <attribute2_defaultvalue>5.5</attribute2_defaultvalue>
        <attribute2_required>1</attribute2_required>
        <attribute2_disabled></attribute2_disabled>
        <attribute3_attributeid>5cb73b04adccf621f8014964</attribute3_attributeid>
        <attribute3_name>English</attribute3_name>
        <attribute3_description></attribute3_description>
        <attribute3_type>4</attribute3_type>
        <attribute3_defaultvalue>false</attribute3_defaultvalue>
        <attribute3_required></attribute3_required>
        <attribute3_disabled></attribute3_disabled>
        <attribute4_attributeid>5cb73b04adccf621f8014965</attribute4_attributeid>
        <attribute4_name>Publish date</attribute4_name>
        <attribute4_description></attribute4_description>
        <attribute4_type>5</attribute4_type>
        <attribute4_defaultvalue>1987-07-15 00:00:00</attribute4_defaultvalue>
        <attribute4_required>1</attribute4_required>
        <attribute4_disabled></attribute4_disabled>
        <attribute5_attributeid>5cb73b04adccf621f8014966</attribute5_attributeid>
        <attribute5_name>Format</attribute5_name>
        <attribute5_description></attribute5_description>
        <attribute5_type>6</attribute5_type>
        <attribute5_defaultvalue>PDF</attribute5_defaultvalue>
        <attribute5_required></attribute5_required>
        <attribute5_disabled></attribute5_disabled>
        <attribute5_predefinedvalue0>PDF</attribute5_predefinedvalue0>
        <attribute5_predefinedvalue1>Kindle</attribute5_predefinedvalue1>
        <attribute5_predefinedvalue2>Text</attribute5_predefinedvalue2>
        <attribute5_predefinedvalue3>Markdown</attribute5_predefinedvalue3>
        <attribute5_predefinedvalues_total>4</attribute5_predefinedvalues_total>
        <attribute6_attributeid>5cb73b04adccf621f8014967</attribute6_attributeid>
        <attribute6_name>Tags</attribute6_name>
        <attribute6_description></attribute6_description>
        <attribute6_type>7</attribute6_type>
        <attribute6_defaultvalue>Self-help,Philosophy</attribute6_defaultvalue>
        <attribute6_required></attribute6_required>
        <attribute6_disabled></attribute6_disabled>
        <attributes_total>7</attributes_total>
    </metadataset>
</metadatasets>
RESPONSE;
    }
}