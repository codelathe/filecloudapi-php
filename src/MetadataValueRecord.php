<?php

/*******************************************************************************
 * Copyright (c) 2019 CodeLathe LLC. All rights Reserved.
 * This file is part of FileCloud  http://www.getfilecloud.com
 *******************************************************************************/

namespace CodeLathe\FileCloudApi;

/**
 * Class MetadataValueRecord
 * @package CodeLathe\FileCloudApi
 */
class MetadataValueRecord extends AbstractMetadataRecord
{
    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $description;

    /**
     * @var int
     */
    private $setType;

    /**
     * @var bool
     */
    private $read;

    /**
     * @var bool
     */
    private $write;

    /**
     * @var array 
     */
    private $attributes = [];

    /**
     * @var int
     */
    private $attributesTotal;
    
    public function __construct($record)
    {
        parent::__construct($record);
        $this->init($record);
        $this->initAttributes($record);
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return int
     */
    public function getSetType(): int
    {
        return $this->setType;
    }

    /**
     * @return bool
     */
    public function isRead(): bool
    {
        return $this->read;
    }

    /**
     * @return bool
     */
    public function isWrite(): bool
    {
        return $this->write;
    }

    /**
     * @return array
     */
    public function getAttributes(): array
    {
        return $this->attributes;
    }

    /**
     * @return int
     */
    public function getAttributesTotal(): int
    {
        return $this->attributesTotal;
    }
    
    private function init(array $record)
    {
        $expectedFields = ['id', 'name', 'description', 'settype', 'read', 'write'];
        $missingFields = array_diff($expectedFields, array_keys($record));
        if ($missingFields) {
            throw new \Exception(sprintf('Missing fields: %s', implode(', ', $missingFields)));
        }

        $this->id = $record['id'];
        $this->name = $record['name'];
        $this->description = $record['description'];
        $this->setType = (int) $record['settype'];
        $this->read = (bool) $record['read'];
        $this->write = (bool) $record['write'];
    }

    private function initAttributes(array $record)
    {
        $attributesNumbers = [];
        foreach ($record as $key => $elem) {
            if ($key === 'attributes_total') {
                $this->attributesTotal = $elem;
                continue;
            }

            if (substr($key, 0, 9) !== 'attribute') {
                continue;
            }

            $_marker = strpos($key, '_', 9);
            if ($_marker === false) {
                throw new \Exception("Malformed attribute: $key at $_marker");
            }

            $i = substr($key, 9, $_marker - 9);
            if (!is_numeric($i)) {
                throw new \Exception("Malformed attribute: $key to $_marker");
            }

            $attributesNumbers[(int) $i] = 1;
        }

        $attributePositions = array_keys($attributesNumbers);
        $recordKeys = array_keys($record);
        foreach ($attributePositions as $attributePosition) {
            $expectedFields = [
                "attribute{$attributePosition}_attributeid",
                "attribute{$attributePosition}_name",
                "attribute{$attributePosition}_description",
                "attribute{$attributePosition}_disabled",
                "attribute{$attributePosition}_required",
                "attribute{$attributePosition}_datatype",
                "attribute{$attributePosition}_value",
            ];

            $missingFields = array_diff($expectedFields, $recordKeys);
            if ($missingFields) {
                throw new \Exception(sprintf('Could not find expected attribute fields: %s', implode(', ', $missingFields)));
            }

            $attributes = [
                "attributeid" => $record["attribute{$attributePosition}_attributeid"],
                "name" => $record["attribute{$attributePosition}_name"],
                "description" => $record["attribute{$attributePosition}_description"],
                "disabled" => (bool) $record["attribute{$attributePosition}_disabled"],
                "required" => (bool) $record["attribute{$attributePosition}_required"],
                "datatype" => (int) $record["attribute{$attributePosition}_datatype"],
                "value" => $this->castToType(
                    $record["attribute{$attributePosition}_value"],
                    (int) $record["attribute{$attributePosition}_datatype"]
                ),
            ];

            // Parse pre-defined values
            if ($attributes['datatype'] === 6) {
                $attributes['enumvalues'] = explode(',', $record["attribute{$attributePosition}_enumvalues"]);
            }

            $this->attributes[] = $attributes;
        }
    }
}