<?php

namespace CodeLathe\FileCloudApi\Tests\Fixtures;

use codelathe\fccloudapi\CloudAdminAPI;

class AccessibleCloudAdminApi extends CloudAdminAPI
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
