<?php

/*******************************************************************************
 * Copyright (c) 2019 CodeLathe LLC. All rights Reserved.
 * This file is part of FileCloud  http://www.getfilecloud.com
 *******************************************************************************/

namespace CodeLathe\FileCloudApi\Tests\Fixtures;

use codelathe\fccloudapi\CloudAPI;

class AccessibleCloudApi extends CloudAPI
{
    /**
     * {@inheritdoc}
     */
    public function __construct($SERVER_URL)
    {
        parent::__construct($SERVER_URL);
    }

    /**
     * {@inheritdoc}
     */
    public function doPOST($url, $postdata)
    {
        return parent::doPOST($url, $postdata);
    }
}
