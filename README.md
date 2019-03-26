# filecloudapi-php
FileCloud API for PHP

FileCloud is a completely self-hosted enterprise file sharing and sync solution and can be run on a variety of platforms and as well as your own cloud infrastructure.(https://www.getfilecloud.com)

# API Class Wrapper for PHP

The API wrapper for FileCloud provides quick API access to almost all of the APIs for user side actions as well as admin side actions. For full API documentation please refer to https://www.getfilecloud.com/developer/

  - CloudAPI class: API for user side APIs
  - CloudAdminAPI class: API for admin side APIs

### Installation

The filecloudapi-php code requires a correctly configured PHP 7.1 environment either on Windows or Linux and above to run

 - PHP curl is required
 - PHP SimpleXML Parser is required
 
There are two ways to include this library on your system:

##### Manual Require

If you can't (or just don't want) to setup composer on your project, you can just require the file `fccloudapi.php` on
your code, and you'll be ready to go.

Take a look on `example1.php` to see it live.

##### Composer

If you have composer set up on your code, all you have to do is `composer require codelathe/filecloudapi-php`,
or include `codelathe/filecloudapi-php` on your `composer.json` file, on the `require` section, and run `composer
update`. You'll should be good to go, since the library will be included on you composer autoloader.

You can take a look on example2 to understand how it works with composer.

### Development

To get started, look at the examples folder. Booth examples there just logs you in into FileCloud and creates a new 
folder.

##### example1 - Manual Require
Look inside `example1.php` and change the constant values at the top of the file. Them execute it:
```sh
$ php example1.php
```

##### example2 - Composer
Go to `example2` folder, and run composer install. It will create the `vendor` folder and the `composer.lock` file.
Look inside `index.php` anc change the constant values at the top of the file, and execute it:
```sh
$ php index.php
```
This example just simulates a project using FileCloudAPI client-library as a dependency. 

### More Information

 Calling APIs will either return a collection object which contains different records or an individual record object where only one record is returned. Collection objects can contain a meta record object that contains general information about the records returned. They also can contain a number of data record objects.
 
  [Collection]  
  
      +--------------- [Meta Record]
      +--------------- [1 ..n Data Records]
  
  Depending upon the API, you might get different types of Data Records Back
  Refer to the API documentation to understand which record type is being returned
 
 For example, here's a non-exhaustive list of types of records that can be returned
 
 [DataRecord]
  
     +----------- [CommandRecord]
     +----------- [FolderPropertiesRecord]
     +----------- [AuthenticationRecord]
     +----------- [ShareRecord]
     +----------- [CommentRecord]
     +----------- [UserRecord]
     +----------- [ProfileRecord]
     +----------- [LangRecord]
     +----------- and so on...

### License

Copyright 2018 CodeLathe Technologies Inc.

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

    http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.

