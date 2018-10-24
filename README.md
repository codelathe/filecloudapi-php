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

### Development

To get started, modify the example1.php and change the server URL, USERNAME and PASSWORD:
```sh
$ php example1.php
```
This simple example logs you in into FileCloud and creates a new folder.

### More Information

 Calling APIs will either return a collection object which contains different records or an individual record object where only one record is returned. Collection objects can contain a meta record object that contains general information about the records returned. They also can contain a number of data record objects.
 
  [Collection]
    |
    +---------------[Meta Record]
    |
    +---------------[1 ..n Data Records]
  
  Depending upon the API, you might get different types of Data Records Back
  Refer to the API documentation to understand which record type is being returned
 
 For example, here's a non-exhaustive list of types of records that can be returned
 [DataRecord]
     |
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

