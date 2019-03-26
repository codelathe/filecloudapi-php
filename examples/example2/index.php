<?php

require_once dirname(__FILE__, 3) . "/vendor/autoload.php";

use codelathe\fccloudapi\CloudAPI;

// change those values to fit your env
define('SERVER_URL', 'https://syourserverurl.com');
define('USERNAME', 'yourusername');
define('PASSWORD', 'yourpassword');
define('FOLDER_NAME', 'mynewfolder');

// ... Cloud Server
$cloudAPI = new CloudAPI(SERVER_URL);

// ... Login the User
$record = $cloudAPI->loginGuest(USERNAME, PASSWORD);

// ... Check if the result is OK
if ($record->getResult() == '1')
    echo "Logged in OK" . PHP_EOL;
else {
    echo "Login Failed" . PHP_EOL;
    exit(-1);
}


// ... Create a new folder
$record = $cloudAPI->createFolder('/' . USERNAME, FOLDER_NAME);

if ($record->getResult() == '1')
    echo "Created a new folder OK. All Done" . PHP_EOL;
else
    echo "Create Folder Failed!" . PHP_EOL;
	
