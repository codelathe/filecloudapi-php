<?php

/*******************************************************************************
 * Copyright (c) 2019 CodeLathe LLC. All rights Reserved.
 * This file is part of FileCloud  http://www.getfilecloud.com
 *******************************************************************************/

namespace CodeLathe\FileCloudApi;

use codelathe\fccloudapi\DataRecord;

abstract class AbstractMetadataRecord extends DataRecord
{
    const TYPE_TEXT = 1;
    const TYPE_INTEGER = 2;
    const TYPE_DECIMAL = 3;
    const TYPE_BOOLEAN = 4;
    const TYPE_DATE = 5;
    const TYPE_ENUMERATION = 6;
    const TYPE_ARRAY = 7;

    /**
     * @param mixed $data
     * @param int $type
     * @return array|bool|\DateTime|float|int
     */
    protected function castToType($data, int $type)
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
}