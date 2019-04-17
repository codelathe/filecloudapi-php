<?php

/*******************************************************************************
 * Copyright (c) 2019 CodeLathe LLC. All rights Reserved.
 * This file is part of FileCloud  http://www.getfilecloud.com
 *******************************************************************************/

namespace CodeLathe\FileCloudApi;

/**
 * Class MetadataSetRecord
 *
 * @package codelathe\fccloudapi
 */
final class MetadataSetRecord extends AbstractMetadataRecord
{
    private $id;
    private $name;
    private $description;
    private $disabled;
    private $read;
    private $write;
    private $attributes = [];
    private $attributesTotal;

    /**
     * MetadataSetRecord constructor.
     * @param $record
     * @throws \Exception
     */
    public function __construct($record)
    {
        parent::__construct($record);
        $this->initMembers($record);
        $this->initAttributes($record);
    }

    /**
     * @param array $record
     * @throws \Exception
     */
    private function initMembers(array $record)
    {
        $expectedFields = ['id', 'name', 'description', 'disabled', 'read', 'write'];
        $missingFields = array_diff($expectedFields, array_keys($record));
        if ($missingFields) {
            throw new \Exception(sprintf('Missing fields: %s', implode(', ', $missingFields)));
        }

        $this->id = $record['id'];
        $this->name = $record['name'];
        $this->description = $record['description'];
        $this->disabled = $record['disabled'];
        $this->read = $record['read'];
        $this->write = $record['write'];
    }

    /**
     * @param array $record
     * @throws \Exception
     */
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
                "attribute{$attributePosition}_type",
                "attribute{$attributePosition}_defaultvalue",
                "attribute{$attributePosition}_required",
                "attribute{$attributePosition}_disabled",
            ];

            $missingFields = array_diff($expectedFields, $recordKeys);
            if ($missingFields) {
                throw new \Exception(sprintf('Could not find expected attribute fields: %s', implode(', ', $missingFields)));
            }

            $attributes = [
                "attributeid" => $record["attribute{$attributePosition}_attributeid"],
                "name" => $record["attribute{$attributePosition}_name"],
                "description" => $record["attribute{$attributePosition}_description"],
                "type" => (int) $record["attribute{$attributePosition}_type"],
                "defaultvalue" => $this->castToType(
                    $record["attribute{$attributePosition}_defaultvalue"],
                    (int) $record["attribute{$attributePosition}_type"]
                ),
                "required" => (bool) $record["attribute{$attributePosition}_required"],
                "disabled" => (bool) $record["attribute{$attributePosition}_disabled"]
            ];

            // Parse pre-defined values
            if ($attributes['type'] === 6) {
                $predefinedValues = [];
                for ($i = 0; ; $i ++) {
                    if (!isset ($record["attribute{$attributePosition}_predefinedvalue{$i}"])) {
                        break;
                    }

                    $predefinedValues[$i] = $record["attribute{$attributePosition}_predefinedvalue{$i}"];
                }

                $attributes['predefinedvalue'] = $predefinedValues;
                $attributes['predefinedvalues_total'] = (int) $record["attribute{$attributePosition}_predefinedvalues_total"];
            }

            $this->attributes[] = $attributes;
        }
    }

    /**
     * @param mixed $data
     * @param int $type
     * @return array|bool|\DateTime|float|int
     */
    private function castToType($data, int $type)
    {
        switch ($type) {
            case self::TYPE_INTEGER:
                return (int) $data;
            case self::TYPE_DECIMAL:
                return (float) $data;
            case self::TYPE_BOOLEAN:
                return (bool) $data;
            case self::TYPE_DATE:
                return \DateTime::createFromFormat('Y-m-d H:i:s', $data);
            case self::TYPE_ARRAY:
                return explode(',', $data);
            default:
                return $data;
        }
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return (string) $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return (string) $this->name;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return (string) $this->description;
    }

    /**
     * @return bool
     */
    public function getDisabled(): bool
    {
        return (bool) $this->disabled;
    }

    /**
     * @return bool
     */
    public function getRead(): bool
    {
        return (bool) $this->read;
    }

    /**
     * @return bool
     */
    public function getWrite(): bool
    {
        return (bool) $this->write;
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
        return (int) $this->attributesTotal;
    }
}