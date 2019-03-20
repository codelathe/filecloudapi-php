<?php
ini_set('display_errors', 1);
require_once dirname(__FILE__, 2) . "/vendor/autoload.php";

use codelathe\fccloudapi\CloudAPI;
//
//define('SERVER_URL', 'https://syourserverurl.com');
//define('USERNAME', 'yourusername');
//define('PASSWORD', 'yourpassword');
//define('FOLDER_NAME', 'mynewfolder');
define('SERVER_URL', 'https://dev1.codelathe.com');
define('USERNAME', 'jeferson.almeida');
define('PASSWORD', '1@oBeliKs');
define('FOLDER_NAME', 'mynewfolder3');


// ... Cloud Server, change the URL to your FileCloud Server URL
$cloudAPI = new CloudAPI(SERVER_URL);

// ... Login the User, change the username and password accordingly
$record = $cloudAPI->loginGuest(USERNAME, PASSWORD);

// ... Check if the result is OK
if ($record->getResult() == '1')
    echo "Logged in OK" . PHP_EOL;
else {
    echo "Login Failed" . PHP_EOL;
    exit(-1);
}


// ... Create a new folder, make sure to change the parent path to be the same as the username
$record = $cloudAPI->createFolder('/' . USERNAME, FOLDER_NAME);

if ($record->getResult() == '1')
    echo "Created a new folder OK. All Done" . PHP_EOL;
else
    echo "Create Folder Failed!" . PHP_EOL;
	
