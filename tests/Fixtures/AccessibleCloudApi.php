<?php

namespace CodeLathe\FileCloudApi\Tests\Fixtures;

use codelathe\fccloudapi\CloudAPI;

/**
 * Class AccessibleCloudApi. Test version of CloudAPI with private properties
 * and methods exposed for easy stubbing.
 * 
 * @package CodeLathe\FileCloudApi\Tests\Fixtures
 */
final class AccessibleCloudApi extends CloudAPI
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
