<?php

 require_once("fccloudapi.php");
 
 use codelathe\fccloudapi\CloudAPI;
 
 // ... Cloud Server, change the URL to your FileCloud Server URL
 $cloudAPI = new CloudAPI("http://yourfilecloudserver.com");
 
 // ... Login the User, change the username and password accordingly
 $record = $cloudAPI->loginGuest("USER", "PASSWORD"); 
  
 // ... Check if the result is OK
 if ($record->getResult() == '1')
    echo "Logged in OK".PHP_EOL;
 else
 {
	 echo "Login Failed".PHP_EOL;
	 exit(-1);
 }
 	

  // ... Create a new folder, make sure to change the parent path to be the same as the username
 $record = $cloudAPI->createFolder('/USER', "mynewfolder");
 
 if ($record->getResult() == '1')
	echo "Created a new folder OK. All Done".PHP_EOL;
 else
	echo "Create Folder Failed!".PHP_EOL;
	
