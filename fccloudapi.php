<?php

/******************************************************************************* 
  Copyright(c) 2015-2018 CodeLathe Technologies Inc. 
 *******************************************************************************/
/**
 * API Classes for FileCloud
 * 
 * Create a CloudAPI object for User Level API commands
 * Create a CloudAdminAPI object for Admin Level API commands
 * 
 * Calling APIs will either return a collection object which contains different records
 * or an individual record object where only one record is returned.
 * 
 * Collection Objects can contain a meta record object that contains general information
 * about the records returned. They also contain a number of data record objects.
 *
 *  [Collection]
 *    |
 *    +---------------[Meta Record]
 *    |
 *    +---------------[1 ..n Data Records]
 * 
 * Depending upon the API, you might get different types of Data Records Back
 * Refer to the API documentation to understand which record type is being returned
 * 
 *  [DataRecord]
 *    |
 *    +----------- [CommandRecord]
 *    +----------- [FolderPropertiesRecord]
 *    +----------- [AuthenticationRecord]
 *    +----------- [ShareRecord]
 *    +----------- [CommentRecord]
 *    +----------- [UserRecord]
 *    +----------- [ProfileRecord]
 *    +----------- [LangRecord]
 *    +----------- and so on...
 *  
 * Usage:
 * /////////////////////////////////////////////////////////////////////////////
 * Create a Cloud API object to work with User level APIs
 * $cloudAPI = new CloudAPI("http://myfilecloudserver.com");
 *
 * * // ... Login the User
 * $record = $cloudAPI->loginGuest("john", "password"); 
 * 
 * // ... Check if the result is OK
 * if ($record->getResult() == '1')
 *  echo "Logged in OK";
 * 
 * // ... Create a new folder
 * $record = $cloudAPI->createFolder('/john', $folder);
 *
 * if ($record->getResult() == '1')
 *  echo "Created a new folder OK";
 * /////////////////////////////////////////////////////////////////////////////
 * Create a Cloud Admin API object to work with User level APIs
 * $cloudAdminAPI = new CloudAdminAPI("http://myfilecloudserver.com");
 *
 * * // ... Login the Admin
 * $record = $cloudAdminAPI->adminLogin("admin", "password"); 
 * 
 * // ... Check if the result is OK
 * if ($record->getResult() == '1')
 *  echo "Logged in Admin OK";
 * ///////////////////////////////////////////////////////////////////////////// 
 * 
 * @since   Oct 10, 2015 — Last update Oct 10, 2015
 * @link    http://www.getfilecloud.com
 * @version 1.0
 */
namespace codelathe\fccloudapi;

class Collection {

    private $m_records;
    private $m_recordName;
    private $m_meta;
    private $m_buffer;
    private $m_success = false;

    public function __construct($buffer, $recordName, $recordType = "DataRecord", $meta = "") {
        $this->m_records = array();
        $this->m_recordName = $recordName;
        $this->m_buffer = $buffer;
        $this->m_meta = $meta;
        
        try {
            $xml = new \SimpleXMLElement($buffer);

            foreach ($xml as $record) {
                $name = $record->getName();

                $array = array();
                foreach ($record as $k => $v) {
                    $array[$k] = (string) $v;
                }

                if ($name == $recordName) {
                    $this->m_records[] = new $recordType($array);
                } else if ($meta != "" && $name == $meta) {
                    $this->m_meta = new DataRecord($array);
                }
            }
            $this->m_success = true;
        } catch (Exception $e) {
            $btrace = $e->getTraceAsString();
            echo 'Caught exception: ', $e->getMessage(), "\n";
            echo 'Trace: ', $btrace, "\n";
            echo $buffer;
            echo "\n";
            die();
        }

        //Kill the script right here if the expected data is not seen
        if ($this->m_success != true) {
            echo "ABORT: Unable to parse response!\nRESPONSE BUFFER\n";
            echo $buffer;
            echo "\n";
            die();
        }
        /*    
        if (count($this->m_records) == 0) {
            echo "WARNING: No record of type (" . $recordName . ") found!\nRESPONSE BUFFER\n";
            echo $buffer;
            echo "\n";
        }
        */
    }

    /**
     * @return int
     */
    public function getNumberOfRecords(): int
    {
        return $this->m_success == true ? count($this->m_records) : 0;
    }

    public function getRecords() {
        return $this->m_records;
    }

    public function getMetaRecord() {
        return $this->m_meta;
    }
}

//------------------------------------------------------------------------------
class DataRecord
{
    protected $m_record;

    public function __construct($record) {
        $this->m_record = $record;
    }

    public function getValueforKey($key) {
        if (isset($this->m_record[$key])) {
            return $this->m_record[$key];
        }
        return false;
    }

    public function getRecord() {
        return $this->m_record;
    }
    
    public function getObjectName()
    {
        return get_class();
    }
}


/**
 * Metadata attribute value utility methods for typecasting.
 * @package codelathe\fccloudapi
 */
trait MetadataAttributeTypeCasterTrait
{
    /**
     * Cast response data to type
     *
     * @param mixed $data
     * @param int $type
     * @return array|bool|\DateTime|float|int
     */
    public function castToType($data, int $type)
    {
        switch ($type) {
            case MetadataAttributeTypes::TYPE_INTEGER:
                return strlen($data) ? (int) $data : null;
            case MetadataAttributeTypes::TYPE_DECIMAL:
                return strlen($data) ? (float) $data : null;
            case MetadataAttributeTypes::TYPE_BOOLEAN:
                return !!json_decode($data);
            case MetadataAttributeTypes::TYPE_DATE:
                $date = \DateTime::createFromFormat('Y-m-d H:i:s', $data);
                
                return $date instanceof \DateTime ? $date : null;
            case MetadataAttributeTypes::TYPE_ARRAY:
                return strlen($data) ? explode(',', $data) : [];
            default:
                return $data;
        }
    }

    /**
     * Best effort reverse cast of attribute value for submission
     * to api endpoint.
     *
     * @param $data
     * @param int $type
     * @return string|int|float|bool
     */
    protected function reverseCastFromType($data, int $type)
    {
        switch ($type) {
            case MetadataAttributeTypes::TYPE_DATE:
                return $data instanceof \DateTime ? $data->format('Y-m-d H:i:s') : $data;
            case MetadataAttributeTypes::TYPE_INTEGER:
            case MetadataAttributeTypes::TYPE_DECIMAL:
            case MetadataAttributeTypes::TYPE_BOOLEAN:
                return json_encode($data);
            case MetadataAttributeTypes::TYPE_ARRAY:
                return is_array($data) ? implode(',', $data): $data;
        }

        return $data;
    }

    /**
     * Best effort guess of datatype
     * 
     * @param $data
     * @return int
     */
    protected function guessType($data)
    {
        if ($data instanceof \DateTime) {
            return MetadataAttributeTypes::TYPE_DATE;
        }
        
        if (is_int($data)) {
            return MetadataAttributeTypes::TYPE_INTEGER;
        }
        
        if (is_float($data)) {
            return MetadataAttributeTypes::TYPE_DECIMAL;
        }
        
        if (is_bool($data)) {
            return MetadataAttributeTypes::TYPE_BOOLEAN;
        }
        
        if (is_array($data)) {
            return MetadataAttributeTypes::TYPE_ARRAY;
        }
        
        return MetadataAttributeTypes::TYPE_TEXT;
    }
}

/**
 * Class MetadataAttributeTypes
 * @package codelathe\fccloudapi
 */
final class MetadataAttributeTypes
{
    const TYPE_TEXT = 1;
    const TYPE_INTEGER = 2;
    const TYPE_DECIMAL = 3;
    const TYPE_BOOLEAN = 4;
    const TYPE_DATE = 5;
    const TYPE_ENUMERATION = 6;
    const TYPE_ARRAY = 7;
    
    private function __construct($record) {}
}

/**
 * Trait MetadataSetTrait
 * @package codelathe\fccloudapi
 */
trait MetadataSetTrait
{
    use MetadataAttributeTypeCasterTrait;

    private $id;
    private $name;
    private $description;
    private $disabled;
    private $attributes = [];
    private $attributesTotal;

    /**
     * @param array $record
     * @throws \Exception
     */
    private function init(array $record)
    {
        $expectedFields = ['id', 'name', 'description', 'disabled'];
        $missingFields = array_diff($expectedFields, array_keys($record));
        if ($missingFields) {
            throw new \Exception(sprintf('Missing fields: %s', implode(', ', $missingFields)));
        }

        $this->id = $record['id'];
        $this->name = $record['name'];
        $this->description = $record['description'];
        $this->disabled = $record['disabled'];
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
                throw new \Exception("Malformed attribute: $key at $_marker");
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

/**
 * Class MetadataSetRecord
 * @package codelathe\fccloudapi
 */
final class MetadataSetRecord extends DataRecord
{
    use MetadataSetTrait;
    
    private $read;
    private $write;

    /**
     * MetadataSetRecord constructor.
     * @param $record
     * @throws \Exception
     */
    public function __construct($record)
    {
        parent::__construct($record);
        $this->init($record);
        $this->initAttributes($record);

        $expectedFields = ['read', 'write'];
        $missingFields = array_diff($expectedFields, array_keys($record));
        if ($missingFields) {
            throw new \Exception(sprintf('Missing fields: %s', implode(', ', $missingFields)));
        }

        $this->read = (bool) $record['read'];
        $this->write = (bool) $record['write'];
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
}

/**
 * Class AdminMetadataSetRecord
 * @package codelathe\fccloudapi
 */
final class AdminMetadataSetRecord extends DataRecord
{
    use MetadataSetTrait;
    
    private $type;
    private $allowAllPaths;
    private $users = [];
    private $usersTotal;
    private $groups = [];
    private $groupsTotal;
    private $paths = [];
    private $pathsTotal;

    /**
     * AdminMetadataSetRecord constructor.
     * @param $record
     * @throws \Exception
     */
    public function __construct(array $record)
    {
        parent::__construct($record);
        $this->init($record);
        $this->initAttributes($record);

        $expectedFields = ['type', 'allowallpaths'];
        $missingFields = array_diff($expectedFields, array_keys($record));
        if ($missingFields) {
            throw new \Exception(sprintf('Missing fields: %s', implode(', ', $missingFields)));
        }
        $this->type = (int) $record['type'];
        $this->allowAllPaths = (bool) $record['allowallpaths'];
        
        $this->initUsers($record);
        $this->initGroups($record);
        $this->initPaths($record);
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @return bool
     */
    public function getAllowAllPaths(): bool
    {
        return $this->allowAllPaths;
    }

    /**
     * @return array
     */
    public function getUsers(): array
    {
        return $this->users;
    }

    /**
     * @return int
     */
    public function getUsersTotal(): int
    {
        return $this->usersTotal;
    }

    /**
     * @return array
     */
    public function getGroups(): array
    {
        return $this->groups;
    }

    /**
     * @return int
     */
    public function getGroupsTotal(): int
    {
        return $this->groupsTotal;
    }

    /**
     * @return array
     */
    public function getPaths(): array
    {
        return $this->paths;
    }

    /**
     * @return int
     */
    public function getPathsTotal(): int
    {
        return $this->pathsTotal;
    }

    /**
     * @param array $record
     * @throws \Exception
     */
    private function initUsers(array $record)
    {
        $indices = $this->extractIndices($record, 'user');
        $recordKeys = array_keys($record);
        foreach ($indices as $index) {
            $expectedFields = [
                "user{$index}_name",
                "user{$index}_read",
                "user{$index}_write",
            ];

            $missingFields = array_diff($expectedFields, $recordKeys);
            if ($missingFields) {
                throw new \Exception(sprintf('Could not find expected user fields: %s', implode(', ', $missingFields)));
            }

            $user = [
                "name" => $record["user{$index}_name"],
                'read' => (bool) $record["user{$index}_read"],
                'write' => (bool) $record["user{$index}_write"],
            ];
            
            $this->users[] = $user;
        }

        $this->usersTotal = (int) $record['users_total'];
    }

    /**
     * @param array $record
     * @throws \Exception
     */
    private function initGroups(array $record)
    {
        $indices = $this->extractIndices($record, 'group');
        $recordKeys = array_keys($record);
        foreach ($indices as $index) {
            $expectedFields = [
                "group{$index}_id",
                "group{$index}_name",
                "group{$index}_read",
                "group{$index}_write",
            ];

            $missingFields = array_diff($expectedFields, $recordKeys);
            if ($missingFields) {
                throw new \Exception(sprintf('Could not find expected user fields: %s', implode(', ', $missingFields)));
            }

            $group = [
                'id' => $record["group{$index}_id"],
                'name' => $record["group{$index}_name"],
                'read' => (bool) $record["group{$index}_read"],
                'write' => (bool) $record["group{$index}_write"],
            ];

            $this->groups[] = $group;
        }

        $this->groupsTotal = (int) $record['groups_total'];
    }

    /**
     * @param array $record
     * @throws \Exception
     */
    private function initPaths(array $record)
    {
        $indices = $this->extractIndices($record, 'path');
        foreach ($indices as $index) {
            $this->paths[] = $record["path{$index}"];
        }

        $this->pathsTotal = (int) $record['paths_total'];
    }

    /**
     * @param array $record
     * @param string $prefix
     * @return array
     * @throws \Exception
     */
    private function extractIndices(array $record, string $prefix)
    {
        $prefixLength = strlen($prefix);
        $indices = [];
        foreach ($record as $key => $elem) {
            if (substr($key, 0, $prefixLength) !== $prefix) {
                continue;
            }

            $_marker = strpos($key, '_', $prefixLength);
            if ($_marker === false) {
                // Maybe this is paths, simply get all chars after prefix
                $_marker = strlen($key);
            }

            $i = substr($key, $prefixLength, $_marker - $prefixLength);
            if (!is_numeric($i)) {
                // Skip {$prefix}s_total
                continue;
            }

            $indices[(int) $i] = 1;
        }
        
        return array_keys($indices);
    }
}

/**
 * Class MetadataValueRecord
 * @package codelathe\fccloudapi
 */
final class MetadataValueRecord extends DataRecord
{
    use MetadataAttributeTypeCasterTrait;

    /**
     * @var string
     */
    private $id;

    /**
     * @var string
     */
    private $name;

    /**
     * @var string
     */
    private $description;

    /**
     * @var int
     */
    private $setType;

    /**
     * @var bool
     */
    private $read;

    /**
     * @var bool
     */
    private $write;

    /**
     * @var array
     */
    private $attributes = [];

    /**
     * @var int
     */
    private $attributesTotal;

    /**
     * MetadataValueRecord constructor.
     * @param $record
     * @throws \Exception
     */
    public function __construct($record)
    {
        parent::__construct($record);
        $this->init($record);
        $this->initAttributes($record);
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return int
     */
    public function getSetType(): int
    {
        return $this->setType;
    }

    /**
     * @return bool
     */
    public function isRead(): bool
    {
        return $this->read;
    }

    /**
     * @return bool
     */
    public function isWrite(): bool
    {
        return $this->write;
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
        return $this->attributesTotal;
    }

    /**
     * @param array $record
     * @throws \Exception
     */
    private function init(array $record)
    {
        $expectedFields = ['id', 'name', 'description', 'settype', 'read', 'write'];
        $missingFields = array_diff($expectedFields, array_keys($record));
        if ($missingFields) {
            throw new \Exception(sprintf('Missing fields: %s', implode(', ', $missingFields)));
        }

        $this->id = $record['id'];
        $this->name = $record['name'];
        $this->description = $record['description'];
        $this->setType = (int) $record['settype'];
        $this->read = (bool) $record['read'];
        $this->write = (bool) $record['write'];
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
                throw new \Exception("Malformed attribute: $key at $_marker");
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
                "attribute{$attributePosition}_disabled",
                "attribute{$attributePosition}_required",
                "attribute{$attributePosition}_datatype",
                "attribute{$attributePosition}_value",
            ];

            $missingFields = array_diff($expectedFields, $recordKeys);
            if ($missingFields) {
                throw new \Exception(sprintf('Could not find expected attribute fields: %s', implode(', ', $missingFields)));
            }

            $attributes = [
                "attributeid" => $record["attribute{$attributePosition}_attributeid"],
                "name" => $record["attribute{$attributePosition}_name"],
                "description" => $record["attribute{$attributePosition}_description"],
                "disabled" => (bool) $record["attribute{$attributePosition}_disabled"],
                "required" => (bool) $record["attribute{$attributePosition}_required"],
                "datatype" => (int) $record["attribute{$attributePosition}_datatype"],
                "value" => $this->castToType(
                    $record["attribute{$attributePosition}_value"],
                    (int) $record["attribute{$attributePosition}_datatype"]
                ),
            ];

            // Parse pre-defined values
            if ($attributes['datatype'] === 6) {
                $attributes['enumvalues'] = explode(',', $record["attribute{$attributePosition}_enumvalues"]);
            }

            $this->attributes[] = $attributes;
        }
    }
}

//------------------------------------------------------------------------------
class CommandRecord extends DataRecord
{
    public function getType() {
        return $this->m_record['type'];
    }

    public function getResult() {
        return $this->m_record['result'];
    }

    public function getMessage() {
        return $this->m_record['message'];
    }
}
//------------------------------------------------------------------------------
class FolderPropertiesRecord extends DataRecord
{
    public function getTotalFolder() {
        return $this->m_record['totalfolders'];
    }

    public function getTotalFiles() {
        return $this->m_record['totalfiles'];
    }

    public function getTotalSize() {
        return $this->m_record['totalsize'];
    }

    public function getVersionedFiles() {
        return $this->m_record['versionedfiles'];
    }

    public function getVersionedSize() {
        return $this->m_record['versionedsize'];
    }

    public function getLiveFiles() {
        return $this->m_record['livefiles'];
    }

    public function getLiveFolders() {
        return $this->m_record['livefolders'];
    }

    public function getLiveSize() {
        return $this->m_record['livesize'];
    }
    
    public function getIncompleteFiles() {
        return $this->m_record['incompletefiles'];
    }
    
    public function getIncompleteSize() {
        return $this->m_record['incompletesize'];
    }
}

// -----------------------------------------------------------------------------
class AuthenticationRecord extends DataRecord
{
	
    public function getProfile() {
        return $this->m_record['profile'];
    }

    public function getDisplayName() {
        return $this->m_record['displayname'];
    }

    public function getPeerID() {
        return $this->m_record['peerid'];
    }

    public function getAuthenticated() {
        return $this->m_record['authenticated'];
    }

    public function getOS() {
        return $this->m_record['OS'];
    }

    public function getAuthType() {
        return $this->m_record['authtype'];
    }
}

//------------------------------------------------------------------------------
class ShareRecord extends DataRecord
{
    public function getShareId()
    {
        return $this->m_record['shareid'];
    }
    
    public function getShareName()
    {
        return $this->m_record['sharename'];
    }
    
    public function getShareLocation()
    {
        return $this->m_record['sharelocation'];
    }
    
    public function getShareOwner()
    {
        return $this->m_record['shareowner'];
    }
    
    public function getShareUrl() {
        return $this->m_record['shareurl'];
    }

    public function getViewmode() {
        return $this->m_record['viewmode'];
    }

    public function getValidityPeriod() {
        return $this->m_record['validityperiod'];
    }

    public function getSharesizeLimit() {
        return $this->m_record['sharesizelimit'];
    }

    public function getMaxdownloads() {
        return $this->m_record['maxdownloads'];
    }

    public function getDownloadCount() {
        return $this->m_record['downloadcount'];
    }

    public function getViewsize() {
        return $this->m_record['viewsize'];
    }

    public function getThumbsize() {
        return $this->m_record['thumbsize'];
    }

    public function getAllowPublicAccess() {
        return $this->m_record['allowpublicaccess'];
    }

    public function getAllowPublicUpload() {
        return $this->m_record['allowpublicupload'];
    }

    public function getAllowPublicViewonly() {
        return $this->m_record['allowpublicviewonly'];
    }

    public function getIsdir() {
        return $this->m_record['isdir'];
    }

    public function getIsvalid() {
        return $this->m_record['isvalid'];
    }

    public function getCreateddDate() {
        return $this->m_record['createddate'];
    }

    public function getAllowEdit() {
        return $this->m_record['allowedit'];
    }

    public function getAllowDelete() {
        return $this->m_record['allowdelete'];
    }

    public function getAllowSync() {
        return $this->m_record['allowsync'];
    }

    public function getAllowShare() {
        return $this->m_record['allowshare'];
    }
    public function getIsPublicSecure() {
        return $this->m_record['ispublicsecure'];
    }

}
//------------------------------------------------------------------------------
class CommentRecord extends DataRecord
{
    public function getId() {
        return $this->m_record['id'];
    }

    public function getwho() {
        return $this->m_record['who'];
    }

    public function getwhen() {
        return $this->m_record['when'];
    }

    public function gettext() {
        return $this->m_record['text'];
    }

}

//------------------------------------------------------------------------------
class UserRecord extends DataRecord
{
    public function getUserName() {
        return $this->m_record['username'];
    }
    public function getEmail() {
        return $this->m_record['email'];
    }
    public function getEmailId() {
        return $this->m_record['emailid'];
    }
    public function getDisplayName() {
        return $this->m_record['displayname'];
    }
    public function getCreated() {
        return $this->m_record['created'];
    }
    public function getStatus() {
        return $this->m_record['status'];
    }
    public function getTotal(){
        return $this->m_record['total'];
    }
    public function getSizeingb(){
        return $this->m_record['sizeingb'];
    }
    public function getSize(){
        return $this->m_record['size'];
    }
    public function getVerfied(){
        return $this->m_record['verified'];
    }
    public function getAdminstatus(){
        return $this->m_record['adminstatus'];
    }
    public function getSharemode(){
        return $this->m_record['sharemode'];
    }
    public function getDisablemyfilessync(){
        return $this->m_record['disablemyfilessync'];
    }
    public function getDisablenetworksync(){
        return $this->m_record['disablenetworksync'];
    }
    public function getlastlogindate(){
        return $this->m_record['lastlogindate'];
    }
    public function getAuthtype(){
        return $this->m_record['authtype'];
    }
    public function getExpirationdate(){
        return $this->m_record['expirationdate'];
    }
    public function getSizeusedwithshares(){
        return $this->m_record['sizeusedwithshares'];
    }
    public function getSizeusedwithoutshares(){
        return $this->m_record['sizeusedwithoutshares'];
    }
    public function getFreespace(){
        return $this->m_record['freespace'];
    }
     
}

//------------------------------------------------------------------------------
class ProfileRecord extends DataRecord
{
    public function getNickName() {
        if (isset($this->m_record['nickname']))
            return $this->m_record['nickname'];
        return '';
    }

    public function getPeerID() {
        if (isset($this->m_record['peerid']))
            return $this->m_record['peerid'];
        return '';
    }

    public function getProfileRoot() {
        if (isset($this->m_record['profileroot']))
            return $this->m_record['profileroot'];
        return '';
    }

    public function getLocation() {
        return $this->m_record['location'];
    }

    public function getDisplayName() {
        return $this->m_record['displayname'];
    }

    public function getEmail() {
        if (isset($this->m_record['profileroot']))
            return $this->m_record['email'];

        return '';
    }
    
    public function getEmailID(){
        return $this->m_record['email'];
    }

    public function getSecretQn() {
        return $this->m_record['secretqn'];
    }

    public function getSecretAns() {
        return $this->m_record['secretans'];
    }

    public function getHint() {
        return $this->m_record['hint'];
    }

    public function getDateForamt() {
        return $this->m_record['dateformat'];
    }

    public function getIsRemote() {
        return $this->m_record['isremote'];
    }

    public function getProfileUserDataDir() {
        return $this->m_record['profileuserdatadir'];
    }

    public function getEmailVerifyTag() {
        return $this->m_record['emailverifytag'];
    }
    
    public function getCreation() {
        return $this->m_record['creation'];
    }
    
    public function getDomain() {
        return $this->m_record['domain'];
    }
    
    public function getReason() {
        return $this->m_record['reason'];
    }

}

//------------------------------------------------------------------------------
class LangRecord extends DataRecord
{
    public function getLangName() {
        return $this->m_record['name'];
    }

    public function getCurrent() {
        return $this->m_record['current'];
    }

}

//------------------------------------------------------------------------------
class GroupRecord extends DataRecord
{
    public function getGroupId() {
        return $this->m_record['groupid'];
    }

    public function getGroupName() {
        return $this->m_record['groupname'];
    }  
    
    public function getEveryoneGroup() {
        return $this->m_record['everyonegroup'];    
    }
    
    public function getCreatedOn() {
        return $this->m_record['createdon'];    
    }
    
    public function getEmailId() {
        return $this->m_record['emailid'];    
    }
    
    public function getAutosynGroup() {
        return $this->m_record['autosyncgroup'];    
    }
}
//------------------------------------------------------------------------------
class EncryptionstatusRecord extends DataRecord
{
    public function getStatuscode() {
        return $this->m_record['statuscode'];
    }
    public function getStatusmsg() {
        return $this->m_record['statusmsg'];
    }
    public function getRecoverykeyactive() {
        return $this->m_record['recoverykeyactive'];
    }
    public function getRecoverykeynotdownloaded() {
        return $this->m_record['recoverykeynotdownloaded'];
    }
}
	
class ExternalRecord extends DataRecord
{
    public function getExternalid() {
        return $this->m_record['externalid'];
    }
    public function getName() {
        return $this->m_record['name'];
    }
    public function getLocation() {
        return $this->m_record['location'];
    }
    public function getNumberofUsers() {
        return $this->m_record['numusers'];
    }
    public function getNumberofGroups() {
        return $this->m_record['numgroups'];
    }
}

// FavoriteRecord
class FavoriteRecord extends DataRecord
{
    public function getId() {
        return $this->m_record['id'];
    }
    public function getName() {
        return $this->m_record['name'];
    }
    public function getParentId() {
        return $this->m_record['parentid'];
    }
    public function getType() {
        return $this->m_record['type'];
    }
    public function getCount() {
        return $this->m_record['count'];
    }
}

// PermissionRecord
class PermissionRecord extends DataRecord
{
    public function getRead() {
        return $this->m_record['read'];
    }
    public function getWrite() {
        return $this->m_record['write'];
    }
    public function getShare() {
        return $this->m_record['share'];
    }
    public function getSync() {
        return $this->m_record['sync'];
    }
    public function getCreate() {
        return $this->m_record['create'];
    }
    public function getUpdate() {
        return $this->m_record['update'];
    }
    public function getDelete() {
        return $this->m_record['delete'];
    }
    public function getIsSharedToYou() {
        return $this->m_record['issharedtoyou'];
    }
    public function getShareOwner(){
        return $this->m_record['shareowner'];
    }
}

// License Record
class LicenseRecord extends DataRecord
{
    public function getAccounts() {
        return $this->m_record['accounts'];
    }
    public function getUsedAccounts() {
        return $this->m_record['usedaccounts'];
    }
    public function getName() {
        return $this->m_record['name'];
    }
    
}

// LanguageRecord
class LanguageRecord extends DataRecord
{
    public function getName() {
        return $this->m_record['name'];
    }
    public function getCurrent() {
        return $this->m_record['current'];
    }
}

// VersionRecord
class VersionRecord extends DataRecord
{
    public function getVersionNumber() {
        return $this->m_record['versionnumber'];
    }
    public function getSize() {
        return $this->m_record['size'];
    }
    public function getCreatedOn() {
        return $this->m_record['createdon'];
    }
    public function getCreatedBy() {
        return $this->m_record['createdby'];
    }
    public function getFileName() {
        return $this->m_record['filename'];
    }
    public function getSizeInBytes() {
        return $this->m_record['sizeinbytes'];
    }
    public function getFileId() {
        return $this->m_record['fileid'];
    }
}

class ConfigSettingRecord extends DataRecord
{
    public function getParam() {
        return $this->m_record['param'];
    }
    public function getValue() {
        return $this->m_record['value'];
    }
    public function getIsValid(){
        return $this->m_record['isvalid'];
    }
}

// EntryRecord
class EntryRecord extends DataRecord
{
    public function getPath() {
        return $this->m_record['path'];
    }
    public function getDirPath() {
        return $this->m_record['dirpath'];
    }
    public function getFileName() {
        return $this->m_record['name'];
    }
    public function getFileExt() {
        return $this->m_record['ext'];
    }
    public function getIsRoot() {
        return $this->m_record['isroot'];
    }
    public function getIsShareable() {
        return $this->m_record['isshareable'];
    }
    public function getIsSyncable() {
        return $this->m_record['issyncable'];
    }
    public function canDownload() {
        return $this->m_record['candownload'];
    }
    public function canUpload() {
        return $this->m_record['canupload'];
    }
    public function getCanFavorite() {
        return $this->m_record['canfavorite'];
    }
    public function getFullFileName() {
        return $this->m_record['fullfilename'];
    }
    public function getSize() {
        return $this->m_record['size'];
    }
    public function getFullSize() {
        return $this->m_record['fullsize'];
    }
    public function getType() {
        return $this->m_record['type'];
    }
    public function getFavoriteId() {
        return $this->m_record['favoriteid'];
    }
    public function getModified() {
        return $this->m_record['modified'];
    }
    public function getModifiedEpoch() {
        return $this->m_record['modifiedepoch'];
    }
    public function getFavoriteListId() {
        return $this->m_record['favoritelistid'];
    }
    public function getName() {
        return $this->m_record['name'];
    }
}

//UserUsageRecord

class UserUsageRecord extends DataRecord
{
    public function getUserName() {
        return $this->m_record['username'];
    }
    public function getSizeUsedWithShares() {
        return $this->m_record['sizeusedwithshares'];
    }
    public function getSizeUsedWithoutShares() {
        return $this->m_record['sizeusedwithoutshares'];
    }
    public function getFreeSpace() {
        return $this->m_record['freespace'];
    }
   
}

// UsageRecord
class UsageRecord extends DataRecord
{
    public function getStorageUsage() {
        return $this->m_record['storageusage'];
    }
    public function getSizeLimit() {
        return $this->m_record['sizelimit'];
    }
    public function getUsagePercent() {
        return $this->m_record['usagepercent'];
    }
    public function getTotalFiles() {
        return $this->m_record['totalfiles'];
    }
    public function getTotalFolders() {
        return $this->m_record['totalfolders'];
    }
    public function getTotalSize() {
        return $this->m_record['totalsize'];
    }
    public function getVersionedSize() {
        return $this->m_record['versionedsize'];
    }
    public function getVersionedFiles() {
        return $this->m_record['versionedfiles'];
    }
    public function getLiveFiles() {
        return $this->m_record['livefiles'];
    }
    public function getLiveFolders() {
        return $this->m_record['livefolders'];
    }
    public function getLiveSize() {
        return $this->m_record['livesize'];
    }
    public function getIncompleteFiles() {
        return $this->m_record['incompletefiles'];
    }
    public function getIncompleteSize() {
        return $this->m_record['incompletesize'];
    }
    public function getRecycleFolders() {
        return $this->m_record['recyclefolders'];
    }
    public function getRecycleFiles() {
        return $this->m_record['recyclefiles'];
    }
    public function getRecycleSize() {
        return $this->m_record['recyclesize'];
    }
}

// ActivityRecord
class ActivityRecord extends DataRecord
{
    public function getPath() {
        return $this->m_record['path'];
    }
    public function getIsFile() {
        return $this->m_record['isfile'];
    }
    public function getParent() {
        return $this->m_record['parent'];
    }
    public function getActionCode() {
        return $this->m_record['actioncode'];
    }
    public function getWho() {
        return $this->m_record['who'];
    }
    public function getWhen() {
        return $this->m_record['when'];
    }
    public function getHow() {
        return $this->m_record['how'];
    }
}

//------------------------------------------------------------------------------
// LockRecord
class LockRecord extends DataRecord
{
    public function getLockrId() {
        return $this->m_record['lockrid'];
    }
    public function getLockuserId() {
        return $this->m_record['lockuserid'];
    }
    public function getLockPath() {
        return $this->m_record['lockpath'];
    }
    public function getLockExpiration() {
        return $this->m_record['lockexpiration'];
    }
    public function getLockReadlock() {
        return $this->m_record['lockreadlock'];
    }
}
//------------------------------------------------------------------------------
// System Status Record
class StatusRecord extends DataRecord
{
    public function getApiLevel() {
        return $this->m_record['apilevel'];
    }
    public function getPeerId() {
        return $this->m_record['peerid'];
    }
    public function getDisplayName() {
        return $this->m_record['displayname'];
    }
    public function getUserStatus() {
        return $this->m_record['userstatus'];
    }
    public function getOs() {
        return $this->m_record['OS'];
    }
    public function getCurrentProfile() {
        return $this->m_record['currentprofile'];
    }
    public function getHttpPort() {
        return $this->m_record['httpport'];
    }
    public function getRelayActive() {
        return $this->m_record['relayactive'];
    }
    public function getServerUrl() {
        return $this->m_record['serverurl'];
    }
    public function getAuthType() {
        return $this->m_record['authtype'];
    }
    public function getMediaSyncStorePath() {
        return $this->m_record['mediasyncstorepath'];
    }
    public function getPasswordMinLength() {
        return $this->m_record['passwordminlength'];
    }
    public function getEmail() {
        return $this->m_record['email'];
    }    
}

//------------------------------------------------------------------------
class UserListRecord extends DataRecord
{
    public function getUserName()
    {
        return $this->m_record['name'];
    }
    public function getWriteMode()
    {
        return $this->m_record['writemode'];
    }
}

//------------------------------------------------------------------------
class GroupListRecord extends DataRecord
{
    public function getGroupName()
    {
        return $this->m_record['groupname'];
    }
    public function getGroupId()
    {
        return $this->m_record['id'];
    }
    public function getWriteMode()
    {
        return $this->m_record['writemode'];
    }
}
//------------------------------------------------------------------------
// Members Record
class MembersRecord extends DataRecord
{
    public function getName() {
        return $this->m_record['name'];
    }
}



//------------------------------------------------------------------------------
// AD Group Record
class AdgroupRecord extends DataRecord
{
    public function GetEntry() {
        return $this->m_record['group'];
    }
}

//------------------------------------------------------------------------------
// AD Group Member Record
class AdgroupMemberRecord extends DataRecord
{
     public function getMembers() {
        return $this->m_record['member'];
    } 
}  
//------------------------------------------------------------------------------
class AdminUsersRecord extends DataRecord
{
    public function getAdminUserName()
    {
        return $this->m_record['name'];
    }
}

//------------------------------------------------------------------------------
class UserOperationsRecord extends DataRecord
{
    public function getOpName()
    {
        return $this->m_record['opname'];
    }
    public function getUpdate()
    {
        return $this->m_record['update'];
    }
}

//------------------------------------------------------------------------------
class AlertsRecord extends DataRecord
{
    public function getRid()
    {
        return $this->m_record['rid'];
    }
    public function getLevel()
    {
        return $this->m_record['level'];
    }
    public function getType()
    {
        return $this->m_record['type'];
    }
    public function getDescription()
    {
        return $this->m_record['description'];
    }
}

//------------------------------------------------------------------------------
class ItemRecord extends DataRecord
{
    public function getWho()
    {
        return $this->m_record['who'];
    }
    public function getFileName()
    {
        return $this->m_record['name'];
    }
    public function getSize()
    {
        return $this->m_record['size'];
    }
    public function getCreated()
    {
        return $this->m_record['created'];
    }
    public function getHow()
    {
        return $this->m_record['how'];
    }
}
class DoNotEmailRecord extends DataRecord
{
    public function getRid()
    {
        return $this->m_record['rid'];
    }
    public function getEmail()
    {
        return $this->m_record['email'];
    }
}

class SiteRecord extends DataRecord
{
    public function getSiteId()
    {
        return $this->m_record['siteid'];
    }
    public function getSiteName()
    {
        return $this->m_record['name'];
    }
    public function getSiteUrl()
    {
        return $this->m_record['host'];
    }
    public function getSiteAllocatedQuota()
    {
        return $this->m_record['allocatedquota'];
    }
    public function getSiteTotalUsers()
    {
        return $this->m_record['totalusers'];
    }
    public function getSiteCurrentUsers()
    {
        return $this->m_record['currentusers'];
    }
    public function getSiteUserQuota()
    {
        return $this->m_record['usedquota'];
    }
}

class AuditRecord extends DataRecord
{
    public function getId()
    {
        return $this->m_record['id'];
    }
    public function getUserName()
    {
        return $this->m_record['username'];
    }
    public function getMessage()
    {
        return $this->m_record['message'];
    }
    public function getIP()
    {
        return $this->m_record['ip'];
    }
    public function getCreatedOn()
    {
        return $this->m_record['createdon'];
    }
    public function getAgent()
    {
        return $this->m_record['agent'];
    }
}

//Import AD group Record
class AdGroupImportRecord extends DataRecord
    {
    public function getImportStatus()
    {
        return $this->m_record['importstatus'];
    }
    public function getNewAccounts()
    {
        return $this->m_record['newaccounts'];
    } 
    public function getImported()
    {
        return $this->m_record['imported'];
    } 
    public function getInGroup()
    {
        return $this->m_record['ingroup'];
    } 
    public function getTotalMembers()
    {
        return $this->m_record['totalmembers'];
    }
    public function getExistingAdRecords()
    {
        return $this->m_record['existingadrecords'];
    } 
    public function getSkippedCount()
    {
        return $this->m_record['skippedcount'];
    }
    public function getImportError()
    {
        return $this->m_record['importerror'];
    }
}

class RMCRecord extends DataRecord
{
    public function getRid()
    {
        return $this->m_record['rid'];
    }
    public function getRemoteClientId()
    {
        return $this->m_record['remote_client_id'];
    } 
    public function getRemoteClientAPILevel()
    {
        return $this->m_record['remote_client_api_level'];
    } 
    public function getRemoteClientDispName()
    {
        return $this->m_record['remote_client_disp_name'];
    } 
    public function getRemoteClientOSType()
    {
        return $this->m_record['remote_client_os_type'];
    }
    public function getRemoteClientAppVersion()
    {
        return $this->m_record['remote_client_app_version'];
    } 
    public function getRemoteClientOSVersion()
    {
        return $this->m_record['remote_client_os_version'];
    }
    public function getIsBlocked()
    {
        return $this->m_record['isblocked'];
    }
    public function getUserName()
    {
        return $this->m_record['userid'];
    }
    public function getPendingActionCount()
    {
        return $this->m_record['pending_actions_count'];
    }
}

class RMCCommandRecord extends DataRecord
{
    public function getRid()
    {
        return $this->m_record['rid'];
    }
    public function getRemoteClientId()
    {
        return $this->m_record['remote_client_id'];
    } 
    public function getRemoteCommandId()
    {
        return $this->m_record['remote_command_id'];
    } 
    public function getMessage()
    {
        return $this->m_record['message'];
    } 
    
}

class UserGroupRecord extends DataRecord
{
    public function getGroupId() {
        return $this->m_record['groupid'];
    }
    public function getGroupName() {
        return $this->m_record['groupname'];
    }
    public function getRead() {
        return $this->m_record['read'];
    }
    public function getWrite() {
        return $this->m_record['write'];
    }
    public function getSync() {
        return $this->m_record['sync'];
    }
    public function getShare() {
        return $this->m_record['share'];
    }
    public function getDownload() {
        return $this->m_record['download'];
    }
    public function getDisallowDelete() {
        return $this->m_record['disallowdelete'];
    }
}

class AclRecord extends DataRecord
{
    public function getUser() {
        return $this->m_record['user'];
    }
    public function getGroupId() {
        return $this->m_record['groupid'];
    }
    public function getPerm() {
        return $this->m_record['perm'];
    }
    public function getFlag() {
        return $this->m_record['flag'];
    }
    public function getType() {
        return $this->m_record['type'];
    }
}

class FCVersionRecord extends DataRecord
{
    public function getRemoteBuild() {
        return $this->m_record['remotebuild'];
    }
    public function getLocalBuild() {
        return $this->m_record['localbuild'];
    }
}
//------------------------------------------------------------------------------
class UserAcessForShareRecord extends DataRecord
{
    public function getUserName() {
        return $this->m_record['name'];
    }

    public function getReadAcess() {
        return $this->m_record['read'];
    }

    public function getWriteAcess() {
        return $this->m_record['write'];
    }

    public function getSyncAccess() {
        return $this->m_record['sync'];
    }
    public function getShareAccess() {
        return $this->m_record['share'];
    }
    public function getDownloadAccess() {
        return $this->m_record['download'];
    }
    public function getDisAllowDeleteAccess() {
        return $this->m_record['disallowdelete'];
    }
}

class UsersForShareRecord extends DataRecord
{
    public function getUserId() {
        return $this->m_record['userid'];
    }

    public function getUserName() {
        return $this->m_record['username'];
    }
}
// FileLockInfo
class FileLockInfo extends DataRecord
{
    public function getLocked() {
        return $this->m_record['locked'];
    }
    public function getReadLock() {
        return $this->m_record['readlock'];
    }
    public function getLockedBy() {
        return $this->m_record['lockedby'];
    }
}
//------------------------------------------------------------------------------
class RssRecord extends DataRecord
{
     public function getTitle() {
        return $this->m_record['title'];
    }
     public function getLink() {
        return $this->m_record['link'];
    }
    public function getDescription() {
        return $this->m_record['description'];
    }
    public function getLanguage() {
        return $this->m_record['language'];
    }
    public function getCopyRight() {
        return $this->m_record['copyright'];
    }
    public function getItem()
        {
        return $this->m_record['item'];
    }  
}

class StatsRecord extends DataRecord
{
    public function getTotalusers() {
        return $this->m_record['totalusers'];
    }
    public function getFullAccessUsers() {
        return $this->m_record['fullaccessusers'];
    }
    public function getGuestAccessUsers() {
        return $this->m_record['guestaccessusers'];
    }
    public function getTotalSize() {
        return $this->m_record['totalsize'];
    }
    public function getGroupsCount() {
        return $this->m_record['groupscount'];
    }
    public function getExternalsCount() {
        return $this->m_record['externalscount'];
    }
    public function getMultiSite() {
        return $this->m_record['multisite'];
    }
    public function getMaxsiteStorage() {
        return $this->m_record['maxsitestorage'];
    }
    public function getMaxSiteUsers() {
        return $this->m_record['maxsiteusers'];
    }
    
}
class InfoRecord extends DataRecord
{
    public function getServiceName() {
        return $this->m_record['servicename'];
    }
    public function getServiceUrl() {
        return $this->m_record['serviceurl'];
    }
    public function getAuthType() {
        return $this->m_record['authtype'];
    }
    public function getStorageperUser() {
        return $this->m_record['storageperuser'];
    }
    
}

class UITranslationRecord extends DataRecord
{
    public function getKey() {
        return $this->m_record['key'];
    }
    public function getValue() {
        return $this->m_record['value'];
    }
}

class PathRecord extends DataRecord
{
    public function getRealPath() {
        return $this->m_record['realpath'];
    }
    public function getValue() {
        return $this->m_record['value'];
    }
}

class VideoCapsRecord extends DataRecord
{
    public function getXcoded() {
        return $this->m_record['xcoded'];
    }
    public function getCanXcode() {
        return $this->m_record['canxcode'];
    }
    public function getHttpLiveStream() {
        return $this->m_record['httplivestream'];
    }
    public function getCanHttpLiveStream() {
        return $this->m_record['canhttplivestream'];
    }
}

class CustomizationRecord extends DataRecord
{
    public function getDeviceNode() {
        return $this->m_record['devicenode'];
    }
    public function getEnabledUICustomization() {
        return $this->m_record['enabled'];
    }
    public function getMobileURL() {
        return $this->m_record['MOBILEURL'];
    }
    public function getDesktopURL() {
        return $this->m_record['DESKTOPURL'];
    }
    public function getWindowTitle() {
        return $this->m_record['WINDOWTITLE'];
    }
    public function getShowNewAccount() {
        return $this->m_record['SHOWNEWACCOUNT'];
    }
    public function getDisableMusic() {
        return $this->m_record['DISABLEMUSIC'];
    }
}


class EmailStatusRecord extends DataRecord
{
    public function getEmailStatus() {
        return $this->m_record['status'];
    }
}

class ConfigItemRecord extends DataRecord
{
    public function getKey() {
        return $this->m_record['key'];
    }
    public function getValue() {
        return $this->m_record['value'];
    }
    public function getName() {
        return $this->m_record['name'];
    }
}

class ExternalRecordForS3Share extends DataRecord
{
    public function getExternalid() {
        return $this->m_record['externalid'];
    }
    public function getName() {
        return $this->m_record['name'];
    }
    public function gettype() {
        return $this->m_record['type'];
    }
    public function getTONIDOCLOUD_S3_REGION() {
        return $this->m_record['TONIDOCLOUD_S3_REGION'];
    }
    public function getTONIDOCLOUD_S3_KEY() {
        return $this->m_record['TONIDOCLOUD_S3_KEY'];
    }
    public function getTONIDOCLOUD_S3_BUCKETNAME() {
        return $this->m_record['TONIDOCLOUD_S3_BUCKETNAME'];
    }
    public function getTONIDOCLOUD_S3_ENDPOINT() {
        return $this->m_record['TONIDOCLOUD_S3_ENDPOINT'];
    }
    public function getTONIDOCLOUD_S3_ENABLE_ENCRYPTION() {
        return $this->m_record['TONIDOCLOUD_S3_ENABLE_ENCRYPTION'];
    }
    public function getTONIDOCLOUD_S3_ENCRYPTION_TYPE() {
        return $this->m_record['TONIDOCLOUD_S3_ENCRYPTION_TYPE'];
    }
    public function getTONIDOCLOUD_S3_ENCRYPTION_KMS_KEY_ID() {
        return $this->m_record['TONIDOCLOUD_S3_ENCRYPTION_KMS_KEY_ID'];
    }
}
class GetWorkFlowRecords extends DataRecord
{
    
    public function getWorkflowId() {
        return $this->m_record['id'];
    }
    public function getName() {
        return $this->m_record['name'];
    }
    public function getActionId() {
        return $this->m_record['actionid'];
    }
    public function getActionParams() {
        return $this->m_record['actionparams'];
    }
    public function getActionSummary() {
        return $this->m_record['actionsummary'];
    }
    public function getConditionId() {
        return $this->m_record['conditionid'];
    }
    public function getConditionParams() {
        return $this->m_record['conditionparams'];
    }
 }
 
 class WorkFlowConditionRecords extends DataRecord
 {
     public function getId() {
        return $this->m_record['id'];
    }
    public function getSummary() {
        return $this->m_record['summary'];
    }
    public function getRequiredParams() {
        return $this->m_record['requiredparams'];
    }
    public function getHelp() {
        return $this->m_record['help'];
    }
 }

class PolicyForUserRecord extends DataRecord
{
    public function getKey() {
        return $this->m_record['key'];
    }
    public function getValue() {
        return $this->m_record['value'];
    }
}

class CheckListRecord extends DataRecord
{
    public function getPercentage() {
        return $this->m_record['percentage'];
    }
    public function getValueforKey($key) {
        if (isset($this->m_record[$key])) {
            return $this->m_record[$key];
        }
        return false;
    }
}

class QueriesRecord extends DataRecord
{
    public function getID() {
        return $this->m_record['id'];
    }
    public function getSummary() {
       return $this->m_record['summary'];
    }
    public function getHelp() {
       return $this->m_record['help'];
    }
}

class ReportsRecord extends DataRecord
{
    public function getID() {
        return $this->m_record['id'];
    }
    public function getName() {
        return $this->m_record['name'];
    }
    public function getQueryID() {
        return $this->m_record['queryid'];
    }
    public function getQueryParams() {
        return $this->m_record['queryparam'];
    }
    public function getQuerySummary() {
        return $this->m_record['querysummary'];
    }
    public function getQueryHelp() {
        return $this->m_record['queryhelp'];
    }
}

class TeamfolderpropertiesRecord extends DataRecord
{
    public function getEnabled() {
        return $this->m_record['enabled'];
    }
    public function getUsername() {
        return $this->m_record['username'];
    }
    public function getAclenabled() {
        return $this->m_record['aclenabled'];
    }
    
}

class PolicyRecord extends DataRecord
{
    public function getPolicyId() {
        return $this->m_record['policyid'];
    }
    public function getPolicyName() {
        return $this->m_record['policyname'];
    }
}
// -----------------------------------------------------------------------
// FILECLOUD API CLASS
// -----------------------------------------------------------------------
class APICore {

    // request debug
    public $debug = false;
    public $debugMessages = [];

    public $curl_handle;
    public $server_url;
    public $start_time;
    public $end_time;
    public $user_agent = 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2228.0 Safari/537.36' ;
    public $xsrf_token;
    public $xsrf_not_set;
    //public $cookie_data;

    public function __construct($SERVER_URL, $debug = false) {
        $this->server_url = $SERVER_URL;
        $this->debug = $debug;
        $this->init($SERVER_URL);
    }

    public function init($SERVER_URL) {
        $this->curl_handle = curl_init();
        curl_setopt($this->curl_handle, CURLOPT_COOKIEJAR, dirname(__FILE__) . DIRECTORY_SEPARATOR . "cookie.txt");
        curl_setopt($this->curl_handle, CURLOPT_COOKIEFILE, dirname(__FILE__) . DIRECTORY_SEPARATOR . "cookie.txt");
        curl_setopt($this->curl_handle, CURLOPT_TIMEOUT, 1200);
        curl_setopt($this->curl_handle, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($this->curl_handle, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($this->curl_handle, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($this->curl_handle, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($this->curl_handle, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($this->curl_handle, CURLOPT_MAXREDIRS, 4);
        curl_setopt($this->curl_handle, CURLOPT_HEADER, 0);
        curl_setopt($this->curl_handle, CURLOPT_USERAGENT, $this->user_agent);
    }
    
    protected function startTimer()
    {
        $this->start_time = microtime(true);
        $this->end_time = $this->start_time;
    }
    
    protected function stopTimer()
    {
        $this->end_time = microtime(true);
    }

    public function elapsed()
    {
        return round(abs($this->end_time - $this->start_time),3);
    }
	
	public function log($msg)
	{
		$fp = fopen('cloudapi.log', 'a');
		fwrite($fp, $msg.PHP_EOL);
		fclose($fp);
	}
    
    public function __destruct() {
        curl_close($this->curl_handle);
        if (file_exists(dirname(__FILE__) . DIRECTORY_SEPARATOR . "cookie.txt")) {
            unlink(dirname(__FILE__) . DIRECTORY_SEPARATOR . "cookie.txt");
        }
    }

    protected function doGET($url) {
        curl_setopt($this->curl_handle, CURLOPT_URL, $url);
        curl_setopt($this->curl_handle, CURLOPT_POST, 0);
        curl_setopt($this->curl_handle, CURLOPT_HTTPGET, 1);
        curl_setopt($this->curl_handle, CURLOPT_HEADER, 0);
		curl_setopt($this->curl_handle, CURLOPT_USERAGENT, $this->user_agent);
        //if (count($this->cookie_data) > 0)
         //   curl_setopt($this->curl_handle, CURLOPT_COOKIE, implode(';',$this->cookie_data));
        if($this->xsrf_not_set != '1')
        {
            $headers = array(
               'X-Requested-With: XMLHttpRequest',
               'X-XSRF-TOKEN: '. $this->xsrf_token,
           );
           curl_setopt($this->curl_handle, CURLOPT_HTTPHEADER, $headers);
        }
        $this->preRequestDebug();
        $result = curl_exec($this->curl_handle);
        $result = $this->afterRequestDebug('GET', $url, '', $result, true);
        return $result;
    }

    protected function doPOST($url, $postdata) {
        curl_setopt($this->curl_handle, CURLOPT_URL, $url);
        curl_setopt($this->curl_handle, CURLOPT_POST, 1); 
        curl_setopt($this->curl_handle, CURLOPT_HTTPGET, 0);
        curl_setopt($this->curl_handle, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($this->curl_handle, CURLOPT_HEADER, 0); 
        curl_setopt($this->curl_handle, CURLOPT_USERAGENT, $this->user_agent);
        //if (count($this->cookie_data) > 0)
        //    curl_setopt($this->curl_handle, CURLOPT_COOKIE, implode(';',$this->cookie_data));
        if($this->xsrf_not_set != '1')
        {
            $headers = array( 
               'X-Requested-With: XMLHttpRequest',
               'X-XSRF-TOKEN: '. $this->xsrf_token,
           );
//           var_dump($headers);
//           var_dump( $this->cookie_data);
           curl_setopt($this->curl_handle, CURLOPT_HTTPHEADER, $headers);
        }
        $this->preRequestDebug();
        $result = curl_exec($this->curl_handle);
        $result = $this->afterRequestDebug('POST', $url, $postdata, $result, true);
        return $result;
    }
	
	protected function parseHeader($result)
	{
		  //Check if http success code 200
        $httpcode = curl_getinfo($this->curl_handle, CURLINFO_HTTP_CODE);
	    if ($httpcode!='200' ) {
            echo "Failed to login, Check DB status or Login Credentials Incorrect, httpcode:". $httpcode;
            //exit(0);
        }
        
        //Get Cookie value
        preg_match_all('/^Set-Cookie:\s*([^;]*)/mi', $result, $matches);
        $cookies = array();
        foreach($matches[1] as $item) {
            parse_str($item, $cookie);
            $cookies = array_merge($cookies, $cookie);
        }
                
        preg_match_all("|<[^>]+>(.*)</[^>]+>|U",
        $result,$out, PREG_PATTERN_ORDER);
        $buffer = $out[0][0].$out[0][1].$out[0][2].$out[0][3];
        
        $collection = new Collection($buffer, "command", CommandRecord::class);
        if ($collection->getNumberOfRecords() > 0)    
        {
            $result = $collection->getRecords()[0]->getResult();
            if($result == '1' || $result == '2')
            {
                //set XSRF Cookie
                if(!isset($cookies['X-XSRF-TOKEN']))
                {
                    $this->xsrf_not_set = '1';
                }
                else
                {
                    $xsrfToken = $cookies['X-XSRF-TOKEN'];
                    $this->xsrf_token = $xsrfToken;
                }
            }
        }
        
        //$this->cookie_data = $matches[1];

        return $buffer;
	}

    protected function preRequestDebug()
    {
        // clean up the debug buffer
        $this->debugMessages = [];

        if ($this->debug) {
            curl_setopt($this->curl_handle, CURLOPT_HEADER, true);
            curl_setopt($this->curl_handle, CURLINFO_HEADER_OUT, true);
        }
    }

    protected function afterRequestDebug(string $method, string $url, string $postData, string $result, $removeHeaders = false): string
    {
        if ($this->debug) {

            // normalize the line breaks
            $result = str_replace("\r\n", "\n", trim($result));

            // request
            $this->debugMessages['Request'] = "$method $url";
            $body = [];
            parse_str($postData, $body);
            $this->debugMessages['Request Body'] = $body;

            // request headers
            $rawRequest = curl_getinfo($this->curl_handle, CURLINFO_HEADER_OUT);
            $rawRequest = str_replace("\r\n", "\n", trim($rawRequest));
            $lines = explode("\n", $rawRequest);
            array_shift($lines); // remove the first line and keep the headers
            $headers = [];
            foreach ($lines as $line) {
                [$header, $value] = explode(':', $line);
                $headers[trim($header)] = trim($value);
            }
            $this->debugMessages['Request Headers'] = $headers;

            // request cookies
            $rawCookies = curl_getinfo($this->curl_handle, CURLINFO_COOKIELIST);
            $cookies = [];
            foreach ($rawCookies as $item) {
                $pieces = preg_split('/\s+/', $item);
                $cookies[$pieces[5]] = $pieces[6];
            }
            $this->debugMessages['Request Cookies'] = $cookies;

            // Response code
            $this->debugMessages['Response Code'] = curl_getinfo($this->curl_handle, CURLINFO_HTTP_CODE);

            // Response Headers and body

            // removing 100 and redirect responses
            $regex = '/^HTTP\/[0-9.]+ [13][0-9]{2} [A-Z][a-z]+/';
            while (preg_match($regex, $result)) {
                $result = preg_replace($regex, '', $result);
                $result = trim($result);
            }

            // splitting headers and body
            if (strpos($result, "\n\n") === false) { // requests with no body
                $rawHeaders = $result;
                $body = '';
            } else {
                [$rawHeaders, $body] = explode("\n\n", $result, 2);
            }
            $lines = explode("\n", trim($rawHeaders));
            array_shift($lines);
            $headers = [];
            foreach ($lines as $line) {
                [$header, $value] = explode(':', $line);
                $headers[trim($header)] = trim($value);
            }
            $this->debugMessages['Response Headers'] = $headers;
            $body = trim($body);
            $this->debugMessages['Response Body'] = $body;

            if ($removeHeaders) {
                return $body;
            }
        }
        return $result;
    }

    protected function doPOSTWithHeader($url, $postdata) {
        //clear token first
        $this->xsrf_token = "";
		//$this->cookie_data = array();
        curl_setopt($this->curl_handle, CURLOPT_URL, $url);
        curl_setopt($this->curl_handle, CURLOPT_POST, 1);
        curl_setopt($this->curl_handle, CURLOPT_HTTPGET, 0);
        curl_setopt($this->curl_handle, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($this->curl_handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->curl_handle, CURLOPT_HEADER, 1);
        $this->preRequestDebug();
        $result = curl_exec($this->curl_handle);
        $this->afterRequestDebug('POST', $url, $postdata, $result);

        return $this->parseHeader($result);
    }
    
    protected function doPOSTWithAgent($url, $postdata, $agent )
    {
		$this->user_agent = $agent;
	    curl_setopt($this->curl_handle, CURLOPT_URL, $url);
        curl_setopt($this->curl_handle, CURLOPT_POST, 1);
        curl_setopt($this->curl_handle, CURLOPT_USERAGENT, $agent);
        curl_setopt($this->curl_handle, CURLOPT_POST, 1);
        curl_setopt($this->curl_handle, CURLOPT_HTTPGET, 0);
        curl_setopt($this->curl_handle, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($this->curl_handle, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($this->curl_handle, CURLOPT_HEADER, 1);
        $this->preRequestDebug();
        $result = curl_exec($this->curl_handle);
        $this->afterRequestDebug('POST', $url, $postdata, $result);

		return $this->parseHeader($result);
    }    

    protected function getCurlValue($filename, $mimetype = '') {
        // PHP 5.5 introduced a CurlFile object that deprecates the old @filename syntax
        // See: https://wiki.php.net/rfc/curl-file-upload
        if (function_exists('curl_file_create')) { 
            return curl_file_create(realpath($filename), $mimetype, $filename);
        }   
        
        // Use the old style if using an older version of PHP
        $value = '@' . realpath($filename);
        
        return $value;
    }

    protected function doUpload($url, $filename) {
        $cfile = $this->getCurlValue($filename);
        $post = array('file_contents' => $cfile);
        curl_setopt($this->curl_handle, CURLOPT_URL, $url);
        curl_setopt($this->curl_handle, CURLOPT_POST, 1);
        curl_setopt($this->curl_handle, CURLOPT_POSTFIELDS, $post);
        curl_setopt($this->curl_handle, CURLOPT_HEADER, 0);
        //curl_setopt($this->curl_handle, CURLOPT_COOKIE, implode(';',$this->cookie_data));
        if($this->xsrf_not_set != '1')
        {
            $headers = array( 
               'X-Requested-With: XMLHttpRequest',
               'X-XSRF-TOKEN: '. $this->xsrf_token,
           );
            curl_setopt($this->curl_handle, CURLOPT_HTTPHEADER, $headers);
        }
        return curl_exec($this->curl_handle);
    }
    
    protected function doLicenseUpload($url, $fileArray) {
        $filename = basename($fileArray['file']['name']);
        $cfile = $this->getCurlValue($filename, 'text/xml');
        $post = array('file' => $cfile);
        curl_setopt($this->curl_handle, CURLOPT_URL, $url);
        curl_setopt($this->curl_handle, CURLOPT_POST, 1);
        curl_setopt($this->curl_handle, CURLOPT_POSTFIELDS,$post);
        curl_setopt($this->curl_handle, CURLOPT_HEADER, 0);
        //curl_setopt($this->curl_handle, CURLOPT_COOKIE, implode(';',$this->cookie_data));
        if($this->xsrf_not_set != '1')
        {
            $headers = array( 
               'X-Requested-With: XMLHttpRequest',
               'X-XSRF-TOKEN: '. $this->xsrf_token,
           );
            curl_setopt($this->curl_handle, CURLOPT_HTTPHEADER, $headers);
        }
        return curl_exec($this->curl_handle);
    }
    
     protected function getCurlValueForChunked($tempfile ,$filename) {
        // PHP 5.5 introduced a CurlFile object that deprecates the old @filename syntax
        // See: https://wiki.php.net/rfc/curl-file-upload
        if (function_exists('curl_file_create')) { 
            return curl_file_create($tempfile, '', $filename);
        }   
        
        // Use the old style if using an older version of PHP
        $value = '@' . $tempfile. ';filename='.$filename;
        
        return $value;
    }
    
    protected function doChunkedUpload($url, $filechunkpath, $filename) {
        $cfile = $this->getCurlValueForChunked($filechunkpath,$filename);
        $post = array('file_contents' => $cfile);
        curl_setopt($this->curl_handle, CURLOPT_URL, $url);
        curl_setopt($this->curl_handle, CURLOPT_POST, 1);
        curl_setopt($this->curl_handle, CURLOPT_POSTFIELDS, $post);
        curl_setopt($this->curl_handle, CURLOPT_HEADER, 0);
        //curl_setopt($this->curl_handle, CURLOPT_COOKIE, implode(';',$this->cookie_data));
        if($this->xsrf_not_set != '1')
        {
            $headers = array( 
               'X-Requested-With: XMLHttpRequest',
               'X-XSRF-TOKEN: '. $this->xsrf_token,
           );
            curl_setopt($this->curl_handle, CURLOPT_HTTPHEADER, $headers);
        }
        return curl_exec($this->curl_handle);
    }
    
    }

class CloudAPI extends APICore {

    use MetadataAttributeTypeCasterTrait;
    
    public function __construct($SERVER_URL, $debug = false) {
        parent::__construct($SERVER_URL, $debug);
    }

    public function __destruct() {
        parent::__destruct();
    }
    
    public function getLastRunTime()
    {
        return $this->elapsed();
    }
    
    // ---- GET AUTHENTICATION INFO API
    // RETURNS a AuthenticationInfo Record
    public function getAuthenticationInfo() {
        $this->startTimer();
        $url = $this->server_url . "/core/getauthenticationinfo";
        $buffer = $this->doGET($url);
        $collection = new Collection($buffer,  "info", AuthenticationRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }

    // ---- LOGIN GUEST API
    // USERNAME: takes a username or email address
    // PASSWORD: takes the password for the specified user
    // RETURNS a CommandRecord
    public function loginGuest($user, $password) {
        $this->startTimer();
        $url = $this->server_url . "/core/loginguest";
        $postdata = 'password=' . $password . '&userid=' . $user;
        $buffer = $this->doPOSTWithHeader($url, $postdata);
        $collection = new Collection($buffer, "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }
    
    public function loginGuestWithAgent($user, $password)
    {
	$url = $this->server_url . "/core/loginguest";
	$postdata = 'password=' . $password . '&userid=' . $user;
	$buffer = $this->doPOSTWithAgent($url, $postdata, $this->user_agent);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }
    // ---- CREATEFOLDER API
    // RETURNS a CommandRecord
    public function createFolder($path, $name) {
        $this->startTimer();
        $url = $this->server_url . "/app/explorer/createfolder";
        $postdata = 'name=' . $name . '&path=' . $path;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }

    // ---- DELETEFILE API
    // RETURNS a CommandRecord
    public function deleteFile($path, $name) {
        $this->startTimer();
        $url = $this->server_url . "/core/deletefile";
        $postdata = 'name=' . $name . '&path=' . $path;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }

    //--- 2FA Login API
    //RETURNS a CommandRecord
    public function twofalogin($userid, $code, $token) {
        $this->startTimer();
        $url = $this->server_url . "/core/2falogin";
        $postdata = 'userid=' . $userid . '&code=' . $code . '&token=' . $token;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }

    //---- Logout API
    //RETURNS a CommandRecord
    public function lockSession() {
        $this->startTimer();
        $url = $this->server_url . "/core/locksession";
        $postdata = "";
        $buffer = $this->doPOST($url, $postdata);
		//$this->cookie_data = array();
		$this->xsrf_token = "";
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }

    //---- Resend 2FA code API
    //RETURNS a CommandRecord
    public function resend2facode($userid, $token) {
        $this->startTimer();
        $url = $this->server_url . "/core/resend2facode";
        $postdata = 'userid=' . $userid . '&token=' . $token;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }

    //---- Lock API
    //RETURNS a CommandRecord
    public function lock($path, $expiration = "", $readlock = "") {
        $this->startTimer();
        $url = $this->server_url . "/core/lock";

        if ($expiration != "" && $expiration != null) {
            $expiration = '&expiration=' . $expiration;
        }
        if ($readlock != "" && $readlock != null) {
            $readlock = '&readlock=' . $readlock;
        }
        $postdata = 'path=' . $path . $expiration . $readlock;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }

    //---- Unlock API
    //RETURNS a CommandRecord
    public function unLock($path) {
        $this->startTimer();
        $url = $this->server_url . "/core/unlock";
        $postdata = 'path=' . $path;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }

    //---- SHOWHIDEACTIVITY API
    //RETURNS a CommanRecord
    public function showhideActivity($collapse) {
        $this->startTimer();
        $url = $this->server_url . "/core/showhideactivity";
        $postdata = 'collapse=' . $collapse;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }

    //---- ADDCOMMENT API
    //RETURNS a CommentRecord for sucess and command record for failure
    public function addComment($fullpath, $parent, $isfile, $text) {
        $this->startTimer();
        $url = $this->server_url . "/core/addcommentforitem";
        $postdata = 'fullpath=' . $fullpath . '&parent=' . $parent . '&isfile=' . $isfile . '&text=' . $text;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "comment", CommentRecord::class);
        if ($collection->getNumberOfRecords() > 0)
        {
            $this->stopTimer();
            return $collection->getRecords()[0];
        }
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        if ($collection->getNumberOfRecords() > 0)
        {
            $this->stopTimer();
            return $collection->getRecords()[0];
        }
        $this->stopTimer();
        return NULL;
    }

    //---- REMOVECOMMENT API
    //RETURNS a CommandRecord
    public function removeComment($fullpath, $id) {
        $this->startTimer();
        $url = $this->server_url . "/core/removecommentforitem";
        $postdata = 'fullpath=' . $fullpath . '&id=' . $id;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        if ($collection->getNumberOfRecords() > 0)
        {
            $this->stopTimer();
            return $collection->getRecords()[0];
        }
        $this->stopTimer();
        return NULL;
    }
    public function updateCommentForItem ($fullpath, $id ){
        $this->startTimer();
        $url = $this->server_url . "/core/updatecommentforitem";
        $postdata = 'fullpath=' . $fullpath . '&id=' . $id;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "comment", CommentRecord::class);
        if ($collection->getNumberOfRecords() > 0)
        {
            $this->stopTimer();
            return $collection->getRecords()[0];
        }
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        if ($collection->getNumberOfRecords() > 0)
        {
            $this->stopTimer();
            return $collection->getRecords()[0];
        }
        $this->stopTimer();
        return NULL;
    }
    //---- ADDFAVORITELIST API
    //RETURNS a CommandRecord
    public function addfavoriteList($name = "") {
        $this->startTimer();
        $url = $this->server_url . "/core/addfavoritelist";
        if ($name != "" && $name != null) {
            $name = '&name=' . $name;
        }
        $postdata = 'name=' . $name;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }

    //---- DELETEFAVORITELIST API
    //RETURNS a CommandRecord
    public function deleteFavoriteList($id) {
        $this->startTimer();
        $url = $this->server_url . "/core/removefavoritelist";
        $postdata = 'id=' . $id;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }
    
    //---- replacefavoritelist API
    //RETURNS a CommandRecord
    public function replaceFavoriteList($id, $count) {
        $this->startTimer();
        $url = $this->server_url . "/core/replacefavoritelist";
        $postdata = 'id=' . $id . '&count=' . $count;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }

    //---- SETFAVORITE API
    //RETURNS a CommandRecord
    public function setFavorite($name, $id) {
        $this->startTimer();
        $url = $this->server_url . "/core/setfavorite";
        $postdata = 'id=' . $id . '&name=' . $name;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }

    //---- ADDFILECHANGENOTIFICATION API
    //RETURNS a CommandRecord
    public function addnotiFicationFilter($path) {
        $this->startTimer();
        $url = $this->server_url . "/core/addnotificationfilter";
        $postdata = 'path=' . $path;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }

    //REMOVEFILECHANGE NOTIFICATION API
    //RETURNS a CommandRecord
    public function removenotiFicationFilter($path) {
        $this->startTimer();
        $url = $this->server_url . "/core/removenotificationfilter";
        $postdata = 'path=' . $path;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }

    //---- ISNOTIFICATIONFILTERSET API
    //RETURNS a CommandRecord
    public function isnotiFicationFilterset($path) {
        $this->startTimer();
        $url = $this->server_url . "/core/isnotificationfilterset";
        $postdata = 'path=' . $path;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }

    //--- Get PRofile Settings API
    //RETURNS a ProfileRecord
    public function getProfileSettings() {
        $this->startTimer();
        $url = $this->server_url . "/core/getprofilesettings";
        $buffer = $this->doPOST($url, '');
        $collection = new Collection($buffer,  "profilesettings", ProfileRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }

    //---Update Password API
    //Returns a CommandRecord
    public function updatePassword($oldpassword, $newpassword) {
        $this->startTimer();
        $url = $this->server_url . "/core/updatepassword";
        $postdata = 'oldpassword=' . $oldpassword . '&newpassword=' . $newpassword;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }

    //---Set Display Name API
    //Returns a CommandRecord
    public function setDisplayName($displayname) {
        $this->startTimer();
        $url = $this->server_url . "/core/setdisplayname";
        $postdata = 'dispname=' . $displayname;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }

    //---Get File List API
    //Returns a FileListRecord
    public function getFileList($path) {
        $this->startTimer();
        $url = $this->server_url . "/core/getfilelist";
        $postdata = 'path=' . $path . '&start=0&limit=10';
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "entry", EntryRecord::class, "meta");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
            return $collection;
        }
        else
        {
            return $collection->getMetaRecord();
        }
        return NULL;
        
    }

    //---Get File Position in List API
    //Returns a CommandRecord
    public function getfilepositioninlist($path) {
        $this->startTimer();
        $url = $this->server_url . "/core/getfilelist";
        $postdata = 'path=' . $path;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }

    //--- Get Language List API
    //RETURNS a LanguageRecord
    public function getLanguageList() {
        $this->startTimer();
        $url = $this->server_url . "/core/getlanguagelist";
        $buffer = $this->doPOST($url, '');
        $collection = new Collection($buffer,  "language", LanguageRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords();
        return NULL;
    }

    //---Change Language in List API
    //Returns a Record
    public function changeLanguage($lang) {
        $this->startTimer();
        $url = $this->server_url . "/core/changelanguage";
        $postdata = 'lang=' . $lang;
        $buffer = $this->doPOST($url, $postdata);
        $this->stopTimer();
        return $buffer;
    }

    //---Change Language in List API
    //Returns a Record
    public function badChangeLanguage() {
        $this->startTimer();
        $url = $this->server_url . "/core/changelanguage";
        $buffer = $this->doPOST($url, '');
        $xml = new SimpleXMLElement($buffer);
        $this->stopTimer();
        return $xml;
    }

    //---Upload API
    //Returns a Record
    public function upload($appname, $path, $filename, $complete=1) {
        $this->startTimer();
        $url = $this->server_url . '/core/upload?appname=' . $appname . '&path=' . $path . '&offset=0&complete='.$complete.'&filename=' . urlencode(basename($filename));
        //echo $url;
        $buffer = $this->doUpload($url, $filename);
        $this->stopTimer();
        return $buffer;
    }
    
     public function chunkedUpload($appname, $filename, $tmpfile, $cloudpath,$offset, $complete=1) {
        $this->startTimer();
        $url = $this->server_url . '/core/upload?appname=' . $appname . '&path=' . $cloudpath . '&offset='.$offset.'&complete='.$complete.'&filename=' . urlencode(basename($filename));
        $buffer = $this->doChunkedUpload($url, $tmpfile, $filename);
        $this->stopTimer();
        return $buffer;
    }

    public function removeFavoriteList($id) {
        $this->startTimer();
        $url = $this->server_url . "/core/removefavoritelist";
        $postdata = 'id=' . $id;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }

    public function renameFile($path, $name, $newname) {
        $this->startTimer();
        $url = $this->server_url . "/core/renamefile";
        $postdata = 'path=' . $path . '&name=' . $name . '&newname=' . $newname;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }

    public function copyFile($path, $name, $copyto) {
        $this->startTimer();
        $url = $this->server_url . "/core/copyfile";
        $postdata = 'path=' . $path . '&name=' . $name . '&copyto=' . $copyto;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }

    public function moveFile($fromname, $toname) {
        $this->startTimer();
        $url = $this->server_url . "/core/renameormove";
        $postdata = 'fromname=' . $fromname . '&toname=' . $toname;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }

    public function rotateFsImage($name, $angle) {
        $this->startTimer();
        $url = $this->server_url . "/core/rotatefsimage";
        $postdata = 'name=' . $name . '&angle=' . $angle;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }

    public function addUsertoShare($userid, $shareid) {
        $this->startTimer();
        $url = $this->server_url . "/core/addusertoshare";
        $postdata = 'userid=' . $userid . '&shareid=' . $shareid;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }

    public function setAllowpublicAccess($shareid, $allowpublicaccess, $allowpublicupload, $allowpublicviewonly, $allowpublicuploadonly ='0') {
        $this->startTimer();
        $url = $this->server_url . "/core/setallowpublicaccess";
        $postdata = 'shareid=' . $shareid . '&allowpublicaccess=' . $allowpublicaccess .
                '&allowpublicupload=' . $allowpublicupload . '&allowpublicviewonly=' . $allowpublicviewonly . '&allowpublicuploadonly=' . $allowpublicuploadonly;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }

    public function getShareForPath($path) {
        $this->startTimer();
        $url = $this->server_url . "/core/getshareforpath";
        $postdata = 'path=' . $path;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "share", ShareRecord::class);
        if ($collection->getNumberOfRecords() > 0)
        {
            $this->stopTimer();
            return $collection->getRecords()[0];
        }
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        if ($collection->getNumberOfRecords() > 0)
        {
            $this->stopTimer();
            return $collection->getRecords()[0];
        }
        $this->stopTimer();
        return NULL;
    }
    public function getShareForID($shareid) {
        $this->startTimer();
        $url = $this->server_url . "/core/getshareforid";
        $postdata = 'shareid=' . $shareid;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "share", ShareRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }    
//        public function addshare($sharelocation)
//        {
//            $url = $this->server_url . "/core/addshare";
//            $postdata = 'sharelocation=' . $sharelocation;
//            $buffer = $this->doPOST($url, $postdata);
//            return new CommandRecord($buffer);
//        }

    public function deleteshare($shareid) {
        $this->startTimer();
        $url = $this->server_url . "/core/deleteshare";
        $postdata = 'shareid=' . $shareid;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }

    public function deleteUserFromShare($userid, $shareid) {
        $this->startTimer();
        $url = $this->server_url . "/core/deleteuserfromshare";
        $postdata = 'userid=' . $userid . '&shareid=' . $shareid;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }

    public function createprofile($profilename, $emailid, $password) {
        $this->startTimer();
        $url = $this->server_url . "/core/createprofile";
        $postdata = 'profile=' . $profilename . '&email=' . $emailid . '&password=' . $password;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }

    public function logout() {
        $this->startTimer();
        $url = $this->server_url . "/admin/?op=logout";
        $postdata = "";
        $buffer = $this->doPOST($url, $postdata);
		//$this->cookie_data = array();
		$this->xsrf_token = "";
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }

    public function getfolderproperties($path) {
        $this->startTimer();
        $url = $this->server_url . "/app/explorer/getfolderproperties";
        $postdata = 'path=' . $path;
        $buffer = $this->doPOST($url, $postdata);
        $xml = new SimpleXMLElement($buffer);
        //echo $buffer;
        $collection = new Collection($buffer,  "usage", FolderPropertiesRecord::class);
        if ($collection->getNumberOfRecords() > 0) 
        {
            $this->stopTimer();
            return $collection;
        }
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        if ($collection->getNumberOfRecords() > 0)
        {
            $this->stopTimer();
            return $collection->getRecords()[0];
        }

        $this->stopTimer();
        return NULL;
    }

    public function unsetfavorite($favoritelistid, $path) {
        $this->startTimer();
        $url = $this->server_url . "/core/unsetfavorite";
        $postdata = 'id=' . $favoritelistid . '&name=' . $path;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];

        return NULL;
    }

    public function updatefavoritelist($favoritelistid, $name) {
        $this->startTimer();
        $url = $this->server_url . "/core/updatefavoritelist";
        $postdata = 'id=' . $favoritelistid . '&name=' . $name;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];

        return NULL;
    }

    public function fileexists($filepath) {
        $this->startTimer();
        $url = $this->server_url . "/core/fileexists";
        $postdata = 'file=' . $filepath;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];

        return NULL;
    }

    public function fileinfo($filepath) {
        $this->startTimer();
        $url = $this->server_url . "/core/fileinfo";
        $postdata = 'file=' . $filepath;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "entry", EntryRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }

    public function addShare($sharelocation, $sharename, $allowpublicaccess) {
        $url = $this->server_url . "/app/websharepro/addshare";
        $postdata = 'sharename=' . $sharename . '&sharelocation=' . $sharelocation .
                '&allowpublicaccess=' . $allowpublicaccess;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "share", ShareRecord::class);
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];

        return NULL;
    }

    public function clearFavoriteList($name) {
        $this->startTimer();
        $url = $this->server_url . "/core/clearfavoritesinnamedlist";
        $postdata = 'name=' . $name;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];

        return NULL;
    }
   
    //--- API for quickshare
    //--- RETURNS a share record
    public function Quickshare($sharelocation) {
        $this->startTimer();
        $url = $this->server_url . "/core/quickshare";
        $postdata = 'sharelocation=' . $sharelocation;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "share", ShareRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];

        return NULL;
    }

    //API for getfsthumbimage
    //---RETURNS a command Record
    public function getfsthumbimage($name, $height, $width) {
        $this->startTimer();
        $url = $this->server_url . '/core/getfsthumbimage?name=' . '/' . SERVER_USER . '/' . $name . '&height=' . $height . '&width=' . $width;
        $buffer = $this->doGET($url);
        $this->stopTimer();
        return $buffer;
    }
    
    //API for getallfavoritelists
    //---Returns a Favorite Record
    public function getallfavoritelists($type) {
        $this->startTimer();
        $url = $this->server_url . '/core/getallfavoritelists';
        $postdata = 'type=' . $type;
        $buffer = $this->doPOST($url, $postdata);
        return new Collection($buffer,  "favoritelist", FavoriteRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        
        return NULL;
    }
         
    //API for getfavoritelistforitem
    //---Returns a Favorite Record
    public function getfavoritelistforitem($path) {
        $this->startTimer();
        $url = $this->server_url . "/core/getfavoritelistforitem";
        $postdata = 'path=' . $path;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "favoritelist", FavoriteRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        
        return NULL;
    }
    // API for subscribe email
    public function subScribe($emailid) {
        $this->startTimer();
        $url = $this->server_url . "/core/subscribe";
        $postdata = 'emailid=' . $emailid;
        $buffer = $this->doPOST($url, $postdata);
        return $buffer;
    }
    // API for unsubscribe email
    public function unsubScribe($emailid) {
        $this->startTimer();
        $url = $this->server_url . "/core/unsubscribe";
        $postdata = 'emailid=' . $emailid;
        $buffer = $this->doPOST($url, $postdata);
        return $buffer;
    }
    
    // API for addgroup to share
    // RETURNS a CommandRecord
    public function addGroupToShare($groupid, $shareid) {
        $this->startTimer();
        $url = $this->server_url . "/core/addgrouptoshare";
        $postdata = 'groupid=' . $groupid . '&shareid=' . $shareid;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];

        return NULL;
    }
    // API for deletegroup to share
    // RETURNS a CommandRecord
    public function deleteGroupFromShare($groupid, $shareid) {
        $this->startTimer();
        $url = $this->server_url . "/core/deletegroupfromshare";
        $postdata = 'groupid=' . $groupid . '&shareid=' . $shareid;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];

        return NULL;
    }
    // API for deletegroupfrom share from ADMIN
    // RETURNS a CommandRecord
    public function deleteGroupFromShareFromAdmin($groupid , $shareid, $adminproxyuserid) {
        $this->startTimer();
        $url = $this->server_url . "/core/deletegroupfromshare";
        $postdata = 'groupid=' . $groupid . '&shareid=' . $shareid . '&adminproxyuserid=' . $adminproxyuserid ;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];

        return NULL;
    }

    //API for getaccessdetailsforpath
    //---Return a permission record
    public function getAccessDetailsForPath($fullpath){
        $this->startTimer();
        $url = $this->server_url . '/core/getaccessdetailsforpath';
        $postdata = 'fullpath=' . $fullpath;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "permission", PermissionRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        if ($collection->getNumberOfRecords() > 0)
        {
            $this->stopTimer();
            return $collection->getRecords()[0];
        }
        return NULL;
    }
    
    //API for getcommentforitem
    //---Returns Comment Record
    public function getCommentsForItem($fullpath){
        $this->startTimer();
        $url = $this->server_url . '/core/getcommentsforitem';
        $postdata = 'fullpath=' . $fullpath;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "comment", CommentRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
            return $collection;
        }
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        if ($collection->getNumberOfRecords() > 0)
        {
            $this->stopTimer();
            return $collection->getRecords()[0];
        }
        return NULL;
    }
    
    public function checkID($name) {
        $this->startTimer();
        $url = $this->server_url . "/core/checkid";
        $postdata = 'id='.$name;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];

        return NULL;
    }
    
    //--- Download API
    public function downloadFile($path, $name, $savepath)
    {
	$url = $this->server_url.'/core/downloadfile?filepath='.$path.'&filename='.$name;
	$filedata = $this->doGET($url);
	$httpcode = curl_getinfo($this->curl_handle, CURLINFO_HTTP_CODE);
	if ($httpcode=='200' ) {
	    $fp = fopen($savepath,'w');
            $filesize = fwrite($fp,$filedata);			
            fclose($fp);			
            return true;
	}
	else {
            return false;
	}
    }
        
    public function getDiskUsageDetails($name,$level) {
        $this->startTimer();
        $url = $this->server_url . "/core/getdiskusagedetails";
        $postdata = 'username='.$name.'&level='.$level;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "usage", UsageRecord::class);
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords();
        return NULL;
    }
    
    public function getFavoritesInList($id) {
        $this->startTimer();
        $url = $this->server_url . "/core/getfavoritesinlist";
        $postdata = 'id='.$id;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "entry", EntryRecord::class,"meta");
        if ($collection->getNumberOfRecords() > 0)
        {
            return $collection;
        }
        return NULL;
    }
    
    public function setUserAccessForShare($shareid,$useremailid,$accessiblity,$accessvalue) {
        $this->startTimer();
        $url = $this->server_url . "/core/setuseraccessforshare";
        $postdata = 'shareid='.$shareid.'&userid='.$useremailid.'&'.$accessiblity.'='.$accessvalue;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        if ($collection->getNumberOfRecords() > 0)
        {
            return $collection->getRecords ()[0];
        }
        return NULL;
    }
    
    //API for getversions
    //---Returns Version Record
    public function getVersions($filepath,$filename){
        $url = $this->server_url . '/core/getversions';
        $postdata = 'filepath=' . $filepath . '&filename=' . $filename;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "version", VersionRecord::class,"meta");
        if ($collection->getNumberOfRecords() > 0)
        {
            return $collection;
        }
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        if ($collection->getNumberOfRecords() > 0)
        {
            $this->stopTimer();
            return $collection->getRecords()[0];
        }
        return NULL;
    }
    
    //API for deleteversions
    //---Returns Command Record
    public function deleteVersion($filepath,$filename,$fileid){
        $url = $this->server_url . '/core/deleteversion';
        $postdata = 'filepath=' . $filepath . '&filename=' . $filename . '&fileid=' . $fileid;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        if ($collection->getNumberOfRecords() > 0)
        {
            return $collection->getRecords()[0];
        }
        return NULL;
    }
    
    //API for deleteallversions
    //---Returns Command Record
    public function deleteAllVersions(){
        $url = $this->server_url . '/core/deleteallversions';
        $buffer = $this->doPOST($url, '');
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        if ($collection->getNumberOfRecords() > 0)
        {
            return $collection->getRecords()[0];
        }
        return NULL;
    }
    
    //API for downloadversionedfile
    public function downloadVersionedFile($path, $name, $fileid, $savepath)
    {
	$url = $this->server_url.'/core/downloadversionedfile?filepath='.$path.'&filename='.$name.'&fileid='.$fileid;
	$filedata = $this->doGET($url);
	$httpcode = curl_getinfo($this->curl_handle, CURLINFO_HTTP_CODE);
        $pos = strpos($filedata, '0');
        if($pos == '52')
        {
            $collection = new Collection($filedata,  "command", CommandRecord::class);
            if ($collection->getNumberOfRecords() > 0)
            {
                return $collection->getRecords()[0];
            }
        }
        else
        {
	    $fp = fopen($savepath,'w');
            $filesize = fwrite($fp,$filedata);			
            fclose($fp);			
            return true;
	}
        
    }
    
    //API for geturlforemail
    //---Returns Command Record
    public function getUrlForEmail($shareid){
        $url = $this->server_url . '/core/geturlforemail';
        $postdata = 'shareid='.$shareid;
        $buffer = $this->doPOST($url, $postdata);
        if(isset($buffer))
        {
            return $buffer;
        }
        return NULL;
    }
    
    //API for getactivitystream
    //---Returns Activity Record
    public function getActivityStream($path){
        $url = $this->server_url . '/core/getactivitystream';
        $postdata = 'parent='.$path;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "activitystreamrecord", ActivityRecord::class);
        if ($collection->getNumberOfRecords() > 0)
        {
            return $collection;
        }
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        if ($collection->getNumberOfRecords() > 0)
        {
            return $collection->getRecords()[0];
        }
        return NULL;
    }
    
    //API for getalllocks
    //Returns of Lock Record
    public  function getAllLocks($userid) {
        $this->startTimer();
        $url = $this->server_url . "/core/getalllocks";
        $postdata = 'userid='.$userid;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "lock", LockRecord::class, "meta");
        $meta = $collection->getMetaRecord();
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection;

        return NULL;
        // 
    }
    
    //API for updateshare
    //Returns Share Record
    public function updateShare($shareid , $sharelocation , $sharename , $viewmode , $validityperiod,
            $sharesizelimit , $maxdownloads , $hidenotification , $sharepassword='') {
        $url = $this->server_url . "/core/updateshare";
        $postdata = 'shareid='.$shareid.'&sharename='.$sharename.'&sharelocation='.$sharelocation.
                '&viewmode='.$viewmode.'&validityperiod='.$validityperiod.'&sharesizelimit='.$sharesizelimit
                .'&maxdownloads='.$maxdownloads.'&hidenotification='.$hidenotification . '&sharepassword=' . $sharepassword;
        $buffer = $this->doPOST($url, $postdata);
        if($buffer != NULL)
        {
            $collection = new Collection($buffer, "share", ShareRecord::class,"meta");
            if ($collection->getNumberOfRecords() > 0)
            {
                return $collection->getRecords()[0];
            }
            else
            {
                return $collection->getMetaRecord();
            }
        }
        return NULL;
    
    }
    
    //API for updatesharelink
    //Returns Command Record
    public function updateShareLink($shareid , $oldsharelink , $newsharelink ) {
        $url = $this->server_url . '/core/updatesharelink';
        $postdata = 'shareid='.$shareid.'&oldsharelink='.$oldsharelink.'&newsharelink='.$newsharelink;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        if ($collection->getNumberOfRecords() > 0)
        {
            return $collection->getRecords()[0];
        }
        return NULL;
//    
    }
    
    //---API share to download publicly shared files
    public function share($path,$savepath)
    {
	$url = $this->server_url.'/app/websharepro/share?path='.$path;
	$filedata = $this->doGET($url);
	$httpcode = curl_getinfo($this->curl_handle, CURLINFO_HTTP_CODE);
	if ($httpcode=='200' ) {
	    $fp = fopen($savepath,'w');
            $filesize = fwrite($fp,$filedata);			
            fclose($fp);			
            return true;
	}
	else {
            return false;
	}
    }
    
    //API for getfsslideimage
    //---RETURNS a command Record
    public function getFsSlideImage($path,$savepath) {
        $this->startTimer();
        $url = $this->server_url . '/core/getfsslideimage?name='.$path;
        $buffer = $this->doGET($url);
        $httpcode = curl_getinfo($this->curl_handle, CURLINFO_HTTP_CODE);
        $pos = strpos($buffer, '0');
        if($pos == '55')
        {
            $collection = new Collection($buffer,  "command", CommandRecord::class);
            if ($collection->getNumberOfRecords() > 0)
            {
                return $collection->getRecords()[0];
            }
        }
        if($httpcode != '200')
        {
            return false;
        }
        else
        {
	    $fp = fopen($savepath,'w');
            $filesize = fwrite($fp,$buffer);			
            fclose($fp);			
            return true;
	}
    }
    
    // API for verify email
    public function verifyEmail($username,$tag) {
        $this->startTimer();
        $url = $this->server_url . "/core/verifyemail?u=".$username.'&tag='.$tag;
        $buffer = $this->doPOST($url, '');
        $this->stopTimer();
        return $buffer;
    }
    
    // API for shorten (shorten longurl)
    public function shorten($longurl) {
        $this->startTimer();
        $url = $this->server_url . "/core/shorten?longurl=".$longurl;
        $buffer = $this->doPOST($url, '');
        $this->stopTimer();
        return $buffer;
    }
    
    public function getVerifyTag($profile){
        $this->startTimer();
        $url = $this->server_url . "/app/testhelper/";
        $postdata = 'op=getverifytag&profile='.$profile;
        $buffer = $this->doPOST($url, $postdata);
        $this->stopTimer();
        return $buffer;
    }
    
    public function get2FACode($profile , $token){
        $this->startTimer();        
        $url = $this->server_url . "/app/testhelper/";
        $postdata = 'op=get2facode&profile='.$profile . '&token='.$token;
        $buffer = $this->doPOST($url, $postdata);
        $this->stopTimer();
        return $buffer;
    }
        
    public function getPassword($profile ){
        $this->startTimer();
        $url = $this->server_url . "/app/testhelper/";
        $postdata = "op=getpassword&profile=".$profile ;
        $buffer = $this->doPOST($url, $postdata);
        $this->stopTimer();
        return $buffer;
    }
    
    public function removeActivity($path){
        $this->startTimer();
        $url = $this->server_url . "/app/testhelper/";
        $postdata = "op=removeactivity&path=".$path ;
        $buffer = $this->doPOST($url, $postdata);
        $this->stopTimer();
        return $buffer;
    }
    
    public function dropAllActivity()
    {
        $this->startTimer();
        $url = $this->server_url . "/app/testhelper/";
        $postdata = "op=dropallactivity" ;
        $buffer = $this->doPOST($url, $postdata);
        $this->stopTimer();
        return $buffer;
    }
            
    public function getFavoritesInNamedList($name){
        $this->startTimer();
        $url = $this->server_url . "/core/getfavoritesinnamedlist?name=".$name;
        $buffer = $this->doPOST($url, '');
        $collection = new Collection($buffer,  "entry", EntryRecord::class);
         if ($collection->getNumberOfRecords() > 0)
        {
            $this->stopTimer();
            return $collection->getRecords()[0];
        }
        return NULL;
     }
     public function getSystemStatus(){
        $this->startTimer();
        $url = $this->server_url . "/core/getsystemstatus";
        $buffer = $this->doPOST($url, '');
        $collection = new Collection($buffer,  "status", StatusRecord::class);
         if ($collection->getNumberOfRecords() > 0)
        {
            $this->stopTimer();
            return $collection->getRecords()[0];
        }
        return NULL;
     }
     
     public function downloadFileMulti($path, $count, $filearray, $savepath ){
        $url = $this->server_url . '/core/downloadfilemulti?count=' . $count . '&filepath=' . $path;
        foreach ($filearray as $key => $value) {
            $url .= "&" . $key . "=" . $value;
        }
        $zipfile = $this->doGET($url);
        $httpcode = curl_getinfo($this->curl_handle, CURLINFO_HTTP_CODE);
	if ($httpcode=='200'&& $zipfile != NULL) {
            $fp = fopen($savepath,'w');
            $filesize = fwrite($fp,$zipfile);			
            fclose($fp);
	    return true;
	}
	else {
            return false;
	}
     }
     
     public function search($location, $keyword = '', $minsizeinkb = '', $maxsizeinkb = ''){
        $this->startTimer();
        $url = $this->server_url . "/core/search";
        $postdata = 'location='.$location.'&keyword='.$keyword.'&minsize='.$minsizeinkb.'&maxsize='.$maxsizeinkb;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "entry", EntryRecord::class, "meta");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
            return $collection;
        }
        else
        {
            return $collection->getMetaRecord();
        }
        return NULL;
     }
     
     public function deletePartialUploads()
     {
         $this->startTimer();
         $url = $this->server_url . "/core/deletepartialuploads";
         $postdata = 'ignorets=1';
         $buffer = $this->doPOST($url, $postdata);
         $collection = new Collection($buffer,  "command", CommandRecord::class);
         $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
            return $collection->getRecords()[0];
        }
        return NULL;
    }
    
    public function getRecentId($ownername)
    {
        $this->startTimer();
        $url = $this->server_url . "/app/testhelper/";
        $postdata = "op=getrecentid&owner=".$ownername ;
        $buffer = $this->doPOST($url, $postdata);
        $this->stopTimer();
        return $buffer;
    }
    
    
    //API emptyrecyclebin
    //Returns Command Record
    public function emptyRecycleBin()
     {
         $this->startTimer();
         $url = $this->server_url . "/app/explorer/emptyrecyclebin";
         $buffer = $this->doPOST($url, '');
         $collection = new Collection($buffer,  "command", CommandRecord::class);
         $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
            return $collection->getRecords()[0];
        }
        return NULL;
    }
    
    //API licensecheck
    //Returns Command Record
    public function licenseCheck()
     {
         $this->startTimer();
         $url = $this->server_url . "/core/licensecheck";
         $buffer = $this->doPOST($url, '');
         $collection = new Collection($buffer,  "command", CommandRecord::class);
         $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
            return $collection->getRecords()[0];
        }
        return NULL;
    }
    
    //API docedit
    //Return html page
    public function docEdit($path) {
        $this->startTimer();
        $url = $this->server_url . "/core/docedit";
        $postdata = 'path' . $path;
        $buffer = $this->doPOST($url, $postdata);
        $this->stopTimer();
        return $buffer;
    }
    
    //API getuploadform
    //Return html page
    public function getUploadForm($shareid) {
        $this->startTimer();
        $url = $this->server_url . "/core/getuploadform?shareid=" . $shareid;
        $buffer = $this->doPOST($url, '');
        $this->stopTimer();
        return $buffer;
    }
    
    //Client Login
    public function rmcLogin($user, $password, $clientid, $clientdispname, $clientapilevel, $clientostype, $clientappversion, $clientosversion)
    {
        $this->startTimer();
        $url = $this->server_url . "/core/loginguest";
        $postdata = 'password=' . $password . '&userid=' . $user . '&remote_client_id=' . $clientid.
                '&remote_client_disp_name=' . $clientdispname . '&remote_client_api_level=' . $clientapilevel.
                '&remote_client_os_type=' . $clientostype . '&remote_client_app_version=' .$clientappversion.
                '&remote_client_os_version=' . $clientosversion;
        $buffer = $this->doPOSTWithAgent($url, $postdata , $clientostype);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }
    
    //API To ackrmccommands
    public function ackrmccommands($rid, $remote_client_id){
        $this->startTimer();
        $url = $this->server_url . "/core/ackrmccommands";
        $postdata = 'command_rids=' . $rid .'&remote_client_id=' . $remote_client_id;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }
    
    //API for getgroupaccessforshare
    //---Returns a group record
    public function getGroupAccessforShare($shareid){
        $this->startTimer();
        $url = $this->server_url . "/core/getgroupaccessforshare";
        $postdata = 'shareid=' . $shareid ;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "group", UserGroupRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];

        return NULL;
    }
    //API for setgroupaccessforshare
    //---Returns a Command Record
    public function setGroupAccessforShare($shareid, $groupid, $download, $write, $share, $sync, $disallowdelete){
        $this->startTimer();
        $url = $this->server_url . "/core/setgroupaccessforshare";
        $postdata = 'groupid=' . $groupid . '&shareid=' . $shareid . '&write='  . $write.  '&share='  . $share. 
                '&sync='  . $sync. '&disallowdelete='  . $disallowdelete;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];

        return NULL;
    }
    //API for leaveshare
    //---Returns a Command Record
    public function leaveShare($path){
        $this->startTimer();
        $url = $this->server_url . "/core/leaveShare";
        $postdata = 'path=' . $path;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];

        return NULL;
    }
    
    //API for addaclentry
    //---Returns a Command Record
    public function addAclEntry($path,$type,$value,$perm,$flag){
        $this->startTimer();
        $url = $this->server_url . "/core/addaclentry";
        $postdata = 'path=' . $path . '&type=' .$type . '&value=' . $value . '&perm=' . $perm . '&flag=' .$flag;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];

        return NULL;
    }
    
    //API for deleteaclentry
    //---Returns a Command Record
    public function deleteAclEntry($path,$type,$value){
        $this->startTimer();
        $url = $this->server_url . "/core/deleteaclentry";
        $postdata = 'path=' . $path . '&type=' .$type . '&value=' . $value;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];

        return NULL;
    }
    
    //API for getacl
    //---Returns a ACL Record
    public function getAcl($path){
        $this->startTimer();
        $url = $this->server_url . "/core/getacl";
        $postdata = 'path=' . $path ;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "ace", AclRecord::class,"meta");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection;

        return NULL;
    }
    
    //API for setaclinheritance
    //---Returns a Command Record
    public function setAclInheritance($path,$inherit){
        $this->startTimer();
        $url = $this->server_url . "/core/setaclinheritance";
        $postdata = 'path=' . $path . '&inherit=' .$inherit ;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];

        return NULL;
    }
    
    //API for deleteacl
    //---Returns a Command Record
    public function deleteAcl($path){
        $this->startTimer();
        $url = $this->server_url . "/core/deleteacl";
        $postdata = 'path=' . $path ;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];

        return NULL;
    }
    
    //API for getEffectiveACL
    //---Returns a PermissionRecord 
    public function getEffectiveACL($path,$emailid){
        $this->startTimer();
        $url = $this->server_url . "/core/geteffectiveacl";
        $postdata = 'path=' . $path . '&emailid=' . $emailid;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "perm", PermissionRecord::class, "meta");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection;

        return NULL;
    }
    
    //API for getrmcclients
    //---Returns a RMCRecord 
    public function getRmcClients($username,$start,$end){
        $this->startTimer();
        $url = $this->server_url . "/core/";
        $postdata = 'op=getrmcclients&userid=' . $username . '&start=' . $start . '&end=' . $end;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "rmc_client", RMCRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection;

        return NULL;
    }
    
    //API for approvedeviceaccess
    //---Returns a CommandRecord 
    public function approveDeviceAccess($remoteClientId){
        $this->startTimer();
        $url = $this->server_url . "/core/";
        $postdata = 'op=approvedeviceaccess&remote_client_id=' . $remoteClientId ;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];

        return NULL;
    }
    
    //API for deletedevice
    //---Returns a CommandRecord 
    public function deletedevice($remoteClientId){
        $this->startTimer();
        $url = $this->server_url . "/core/";
        $postdata = 'op=deletedevice&remote_client_id=' . $remoteClientId ;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];

        return NULL;
    }
    
    //API for loginprotectedshare
    //---Returns a CommandRecord 
    public function loginProtectedShare($fullquerystring, $password, $path){
        $this->startTimer();
        $url = $this->server_url . "/core/loginprotectedshare";
        $postdata = 'fullquerystring=' . $fullquerystring . '&password=' . $password . '&path=' . $path ;
        $buffer = $this->doPOSTWithHeader($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];

        return NULL;
    }
    
    //API for getrandompassword
    //---Returns a CommandRecord 
    public function getRandomPassword(){
        $this->startTimer();
        $url = $this->server_url . "/core/getrandompassword";
        $buffer = $this->doPOST($url, '');
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];

        return NULL;
    }
    
    //API for getpublicshareauthinfo
    //---Returns a CommandRecord 
    public function getPublicShareAuthInfo($path){
        $this->startTimer();
        $url = $this->server_url . "/core/getpublicshareauthinfo";
        $postdata = 'path=' . $path ;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];

        return NULL;
    }
    
    //API for getsharepassword
    //---Returns a CommandRecord 
    public function getSharePassword($shareid){
        $this->startTimer();
        $url = $this->server_url . "/core/getsharepassword";
        $postdata = 'shareid=' . $shareid ;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];

        return NULL;
    }
    
    //API for requestdeviceaccess
    //---Returns a CommandRecord 
    public function requestDeviceAccess($userid , $clientid, $clientdispname, $clientapilevel, $clientostype, $clientappversion, $clientosversion){
        $this->startTimer();
        $url = $this->server_url . "/core/requestdeviceaccess";
        $postdata = 'userid=' . $userid . '&remote_client_id=' . $clientid.
                '&remote_client_disp_name=' . $clientdispname . '&remote_client_api_level=' . $clientapilevel.
                '&remote_client_os_type=' . $clientostype . '&remote_client_app_version=' .$clientappversion.
                '&remote_client_os_version=' . $clientosversion;;
        $buffer = $this->doPOSTWithAgent($url, $postdata, $clientostype);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];

        return NULL;
    }
    
    //Client Login
    public function rmcLoginWithCode($user, $code, $clientid, $clientdispname, $clientapilevel, $clientostype, $clientappversion, $clientosversion)
    {
        $this->startTimer();
        $url = $this->server_url . "/core/loginguest";
        $postdata = 'dsc=' . $code . '&userid=' . $user . '&remote_client_id=' . $clientid.
                '&remote_client_disp_name=' . $clientdispname . '&remote_client_api_level=' . $clientapilevel.
                '&remote_client_os_type=' . $clientostype . '&remote_client_app_version=' .$clientappversion.
                '&remote_client_os_version=' . $clientosversion;
        $buffer = $this->doPOSTWithAgent($url, $postdata , $clientostype);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }
    
    //API for createprofileoninvite
    //---Returns a CommandRecord 
    public function createProfileOnInvite($emailid){
        $this->startTimer();
        $url = $this->server_url . "/core/createprofileoninvite";
        $postdata = 'email=' . $emailid ;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];

        return NULL;
    }
    
      
    public function getShares() {
        $this->startTimer();
        $url = $this->server_url . "/core/getshares";
        $buffer = $this->doPOST($url, '');
        $collection = new Collection($buffer,  "share", ShareRecord::class);
        $this->stopTimer();
       if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    } 
    
     public function getUserAccessForShare($shareid) {
        $this->startTimer();
        $url = $this->server_url . "/core/getuseraccessforshare";
        $postdata = 'shareid=' . $shareid;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "user", UserAcessForShareRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    } 
     public function getUsersForShare($shareid) {
        $this->startTimer();
        $url = $this->server_url . "/core/getusersforshare";
        $postdata = 'shareid=' . $shareid;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "user", UsersForShareRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }
    
    //---- Getlockinfo API
    //RETURNS a CommandRecord
    public function getLockInfo($path) {
        $this->startTimer();
        $url = $this->server_url . "/core/getfilelockinfo";
        $postdata = 'path=' . $path;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "filelockinfo", FileLockInfo::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }
    
      public function getRssFeed(){
      $this->startTimer();
        $url = $this->server_url . "/core/getrssfeed";
        $buffer = $this->doPOST($url,'');
        //var_dump($buffer);
        $collection = new Collection($buffer,  "channel", RssRecord::class);
        //var_dump($collection);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection;

        return NULL;
  }

    //---- searchgroups API
    //RETURNS a GroupRecord
    public function searchGroups() {
        $this->startTimer();
        $url = $this->server_url . "/core/searchgroups";
        $buffer = $this->doPOST($url, '');
        $collection = new Collection($buffer,  "group", GroupRecord::class, "meta");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection;
        return NULL;
    }
    
    //---- searchprofiles API
    //RETURNS a UserRecord
    public function searchProfiles($filter) {
        $this->startTimer();
        $url = $this->server_url . "/core/searchprofiles";
        $postdata = 'filter=' . $filter;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "profile", UserRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection;
        return NULL;
    }
    
    public function getProfileImage($savepath)
    {
        $url = $this->server_url.'/core/getprofileimage';
	$filedata = $this->doGET($url);
	$httpcode = curl_getinfo($this->curl_handle, CURLINFO_HTTP_CODE);
	if ($httpcode=='200' ) {
	    $fp = fopen($savepath,'w');
            $filesize = fwrite($fp,$filedata);			
            fclose($fp);			
            return true;
	}
	else {
            return false;
	}
    }
    
    public function getCustomImage($type,$savepath)
    {
        $url = $this->server_url.'/core/getcustomimage?type='.$type;
	$filedata = $this->doGET($url);
	$httpcode = curl_getinfo($this->curl_handle, CURLINFO_HTTP_CODE);
	if ($httpcode=='200' ) {
	    $fp = fopen($savepath,'w');
            $filesize = fwrite($fp,$filedata);			
            fclose($fp);			
            return true;
	}
	else {
            return false;
	}
    }
    
    public function getFavIcon($savepath)
    {
        $url = $this->server_url.'/core/?op=favicon.ico';
	$filedata = $this->doGET($url);
	$httpcode = curl_getinfo($this->curl_handle, CURLINFO_HTTP_CODE);
	if ($httpcode=='200' ) {
	    $fp = fopen($savepath,'w');
            $filesize = fwrite($fp,$filedata);			
            fclose($fp);			
            return true;
	}
	else {
            return false;
	}
    }
    
    public function getRobotsTxt($savepath)
    {
        $url = $this->server_url.'/core/?op=robots.txt';
	$filedata = $this->doGET($url);
	$httpcode = curl_getinfo($this->curl_handle, CURLINFO_HTTP_CODE);
	if ($httpcode=='200' ) {
	    $fp = fopen($savepath,'w');
            $filesize = fwrite($fp,$filedata);			
            fclose($fp);			
            return true;
	}
	else {
            return false;
	}
    }
    
    public function getMetaDataInfo() {
        $this->startTimer();
        $url = $this->server_url . "/core/getmetadatainfo";
        $buffer = $this->doPOST($url, '');
        return $buffer;
    }

    /**
     * Retrieve available metadata sets
     *
     * @param string $fullPath
     * @return Collection
     */
    public function getAvailableMetadataSets(string $fullPath): Collection
    {
        $this->startTimer();
        $response = $this->doPOST("{$this->server_url}/core/getavailablemetadatasets", http_build_query([
            'fullpath' => $fullPath
        ]));
        $collection = new Collection($response,  "metadataset", MetadataSetRecord::class);
        $this->stopTimer();
        
        return $collection;
    }

    /**
     * Retrieve metadata attribute values attached to the specified file object
     *
     * @param string $fullPath
     * @return mixed|boolean
     */
    public function getMetadataValues(string $fullPath)
    {
        $this->startTimer();
        $response = $this->doPOST("{$this->server_url}/core/getmetadatavalues", http_build_query([
            'fullpath' => $fullPath
        ]));
        $collection = new Collection($response,  'metadatasetvalue', MetadataValueRecord::class);
        $this->stopTimer();
        
        return $collection;
    }

    /**
     * Get metadata sets for search
     * 
     * @param string $fullPath
     * @return Collection|null
     */
    public function getMetadataSetsForSearch(string $fullPath): ?Collection
    {
        $this->startTimer();
        $response = $this->doPOST("{$this->server_url}/core/getmetadatasetsforsearch", http_build_query([
            'fullpath' => $fullPath
        ]));
        $collection = new Collection($response,  'metadataset', MetadataSetRecord::class);
        $this->stopTimer();

        return $collection;
    }

    /**
     * Add specified metadata set to file object
     * 
     * @param string $fullPath
     * @param string $setId
     * @return CommandRecord|null
     */
    public function addSetToFileObject(string $fullPath, string $setId): ?CommandRecord
    {
        $this->startTimer();
        $response = $this->doPOST("{$this->server_url}/core/addsettofileobject", http_build_query([
            'fullpath' => $fullPath,
            'setid' => $setId,
        ]));
        $collection = new Collection($response,  'command', CommandRecord::class);
        $records = $collection->getRecords();
        $record = $collection->getNumberOfRecords() > 0 ? reset($records) : null;
        $this->stopTimer();

        return $record;
    }

    /**
     * Remove specified metadata set from file object
     * 
     * @param string $fullPath
     * @param string $setId
     * @return CommandRecord|null
     */
    public function removeSetFromFileObject(string $fullPath, string $setId): ?CommandRecord
    {
        $this->startTimer();
        $response = $this->doPOST("{$this->server_url}/core/removesetfromfileobject", http_build_query([
            'fullpath' => $fullPath,
            'setid' => $setId,
        ]));
        $collection = new Collection($response,  'command', CommandRecord::class);
        $records = $collection->getRecords();
        $record = $collection->getNumberOfRecords() > 0 ? reset($records) : null;
        $this->stopTimer();

        return $record;
    }
    
    /**
     * Update attribute values of a file object's metadata
     * 
     * @param string $setId
     * @param string $fullPath
     * @param array $attributes
     * @return CommandRecord|null
     */
    public function saveAttributeValues(string $fullPath, string $setId, array $attributes): ?CommandRecord
    {
        $this->startTimer();
        $args = [
            'fullpath' => $fullPath,
            'setid' => $setId,
        ];
        
        foreach ($attributes as $i => $attribute) {
            $args["attribute{$i}_attributeid"] = $attribute['attributeid'];
            $args["attribute{$i}_value"] = $this->reverseCastFromType($attribute['value'], $this->guessType($attribute['value']));
        }

        $args['attributes_total'] = count($attributes);
        
        $response = $this->doPOST("{$this->server_url}/core/saveattributevalues", http_build_query($args));
        $collection = new Collection($response,  'command', CommandRecord::class);
        $records = $collection->getRecords();
        $record = $collection->getNumberOfRecords() > 0 ? reset($records) : null;
        $this->stopTimer();

        return $record;
    }
    
    public function getUITranslations() {
        $this->startTimer();
        $url = $this->server_url . "/core/getuitranslations?tag=core";
        $buffer = $this->doPOST($url,'');
        $record = gzdecode($buffer);
        $collection = new Collection($record,  "translation", UITranslationRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection;
        return NULL;
    }
    
    public function getCustomizationData() {
        $this->startTimer();
        $url = $this->server_url . "/core/getcustomizationdata";
        $buffer = $this->doGET($url);
        $collection = new Collection($buffer,  "customdata", CustomizationRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }
    
    public function getVideo($filepath,$savepath)
    {
        $url = $this->server_url.'/core/getvideo?filepath='.$filepath;
	$filedata = $this->doGET($url);
	$httpcode = curl_getinfo($this->curl_handle, CURLINFO_HTTP_CODE);
	if ($httpcode=='200' ) {
	    $fp = fopen($savepath,'w');
            $filesize = fwrite($fp,$filedata);			
            fclose($fp);			
            return true;
	}
	else {
            return false;
	}
    }
    
    public function getAudio($filepath,$savepath)
    {
        $url = $this->server_url.'/core/getaudio?filepath='.$filepath;
	$filedata = $this->doGET($url);
	$httpcode = curl_getinfo($this->curl_handle, CURLINFO_HTTP_CODE);
	if ($httpcode=='200' ) {
	    $fp = fopen($savepath,'w');
            $filesize = fwrite($fp,$filedata);			
            fclose($fp);			
            return true;
	}
	else {
            return false;
	}
    }
    
    public function beginProfileCreation() {
        $this->startTimer();
        $url = $this->server_url . "/core/beginprofilecreation";
        $buffer = $this->doPOST($url, '');
        $collection = new Collection($buffer,  "profilecreationrecord", ProfileRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }
    
    
    public function getTos($savepath)
    {
        $url = $this->server_url.'/core/gettos';
	$filedata = $this->doGET($url);
	$httpcode = curl_getinfo($this->curl_handle, CURLINFO_HTTP_CODE);
	if ($httpcode=='200' ) {
	    $fp = fopen($savepath,'w');
            $filesize = fwrite($fp,$filedata);			
            fclose($fp);			
            return true;
	}
	else {
            return false;
	}
    }
    
    public function splitFilePaths($path) {
        $this->startTimer();
        $url = $this->server_url . "/core/splitfilepaths";
        $postdata = 'path='.$path;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "path", PathRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection;
        return NULL;
    }
    
    public function getAuthURL() {
        $this->startTimer();
        $url = $this->server_url . "/core/getauthurl";
        $buffer = $this->doGET($url);
        return $buffer;
    }
    
    public function getVideoCaps() {
        $this->startTimer();
        $url = $this->server_url . "/core/getvideocaps";
        $buffer = $this->doGET($url);
        $collection = new Collection($buffer,  "caps", VideoCapsRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }
    
    //API for getprivateurlforemail
    //---Returns Command Record
    public function getPrivateUrlForEmail($shareid){
        $url = $this->server_url . '/core/geturlforemail';
        $postdata = 'shareid='.$shareid;
        $buffer = $this->doPOST($url, $postdata);
        if(isset($buffer))
        {
            return $buffer;
        }
        return NULL;
    }
    
    public function getEmailSubject() {
        $this->startTimer();
        $url = $this->server_url . "/core/";
        $postdata = "op=getemailsubject&param=CUSTOMIZATION_EMAIL_SUBJECT_addusertoshare";
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "setting", ConfigSettingRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }
    
    public function sendEmail($message,$from,$toemailid,$bccemailid,$replyto,$subject) {
        $this->startTimer();
        $url = $this->server_url . "/core/sendemail";
        $postdata = 'message='.$message.'&from='.$from.'&toemailid='.$toemailid.'&bccemailid='.$bccemailid.'&replyto='.$replyto
                .'&subject='.$subject;
        $buffer = $this->doPOST($url, $postdata);
        return $buffer;
    }
    
    public function sendShareToEmail($message,$from,$toemailid,$replyto,$sharename,$shareurl,$sharelocation,$publicshareid) {
        $this->startTimer();
        $url = $this->server_url . "/app/websharepro/sendsharetoemail";
        $postdata = 'message='.$message.'&from='.$from.'&toemailid='.$toemailid.'&sharename='.$sharename.'&url='.$shareurl
                .'&sharelocation='.$sharelocation.'&publicshare='.$publicshareid.'&replyto='.$replyto;
        $buffer = $this->doPOST($url, $postdata);
        return $buffer;
    }
    
    public function getEmailTemplate($templatename,$sharename,$shareurl,$toemailid) {
        $this->startTimer();
        $url = $this->server_url . "/core/";
        $postdata = 'op=getemailtemplate&templatename='.$templatename.'&sharename='.$sharename.'&shareurl='.$shareurl.'&toemailid='.$toemailid;
        $buffer = $this->doPOST($url, $postdata);
        return $buffer;
        
    }
    
    
    public function docConvert($path,$savepath)
    {
	$url = $this->server_url.'/core/docconvert?name='.$path;
	$filedata = $this->doGET($url);
	$httpcode = curl_getinfo($this->curl_handle, CURLINFO_HTTP_CODE);
	if ($httpcode=='200' ) {
	    $fp = fopen($savepath,'w');
            $filesize = fwrite($fp,$filedata);			
            fclose($fp);			
            return true;
	}
	else {
            return false;
	}
    }
    
    public function combinePDF($filepath,$count,$filename1,$filename2,$savepath)
    {
	$url = $this->server_url.'/core/combinepdf?count='.$count.'&filepath='.$filepath
                .'&fn1='.$filename1.'&fn2='.$filename2;
	$filedata = $this->doGET($url);
	$httpcode = curl_getinfo($this->curl_handle, CURLINFO_HTTP_CODE);
	if ($httpcode=='200' ) {
	    $fp = fopen($savepath,'w');
            $filesize = fwrite($fp,$filedata);			
            fclose($fp);			
            return true;
	}
	else {
            return false;
	}
    }
    
    public function getActivityByLevel() {
        $this->startTimer();
        $url = $this->server_url . "/core/getactivitybylevel";
        $buffer = $this->doPOST($url, '');
        return $buffer;
    }
    
    public function getPrivacy($savepath)
    {
        $url = $this->server_url.'/core/getprivacy';
	$filedata = $this->doGET($url);
	$httpcode = curl_getinfo($this->curl_handle, CURLINFO_HTTP_CODE);
	if ($httpcode=='200' ) {
	    $fp = fopen($savepath,'w');
            $filesize = fwrite($fp,$filedata);			
            fclose($fp);			
            return true;
	}
	else {
            return false;
	}
    }
    
    public function getAck($savepath)
    {
        $url = $this->server_url.'/core/getack';
	$filedata = $this->doGET($url);
	$httpcode = curl_getinfo($this->curl_handle, CURLINFO_HTTP_CODE);
	if ($httpcode=='200' ) {
	    $fp = fopen($savepath,'w');
            $filesize = fwrite($fp,$filedata);			
            fclose($fp);			
            return true;
	}
	else {
            return false;
	}
    }
    
    public function doCron($key) {
        $this->startTimer();
        $url = $this->server_url . "/core/docron";
        $postdata = 'key=' . $key . '&forced=1';
        $buffer = $this->doPOST($url, $postdata);
        return true;
    }
    
    public function setConfigItem($key, $name, $value) {
        $this->startTimer();
        $url = $this->server_url . "/core/setconfigitem";
        $postdata = 'key=' . $key.'&name='.$name.'&value='.$value;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];

        return NULL;
    }
    
    public function getConfigItem($key, $name, $value) {
        $this->startTimer();
        $url = $this->server_url . "/core/getconfigitem";
        $postdata = 'key=' . $key.'&name='.$name.'&defaultvalue='.$value;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "configitem", ConfigItemRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];

        return NULL;
    }
    
    public function getCssEntries() {
        $this->startTimer();
        $url = $this->server_url . "/core/getcssentries";
        $buffer = $this->doPOST($url, '');
        return $buffer;
    }
    
    //Returns a Record
    public function getfilelistforshare($path, $start = "", $limit = "", $sortby , $sortdir) {
        $url = $this->server_url . "/core/getfilelist";
        $postdata = 'path=' . $path . 'sortby=' .$sortby . 'sortdir=' . $sortdir;
        if ($start != "" && $limit != "") {
            $postdata .= "&start=" . $start;
            $postdata .= "&limit=" . $limit;
        }
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "entry", EntryRecord::class, "meta");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
            return $collection;
        }
        else
        {
            return $collection->getMetaRecord();
        }
        return NULL;
    }

    public function logoutProfile()
    {
        $this->startTimer();
        $url = $this->server_url . '/core/?op=logoutprofile';
        $postdata = '';
        $buffer = $this->doPOST($url, $postdata);
        $this->xsrf_token = '';
        $collection = new Collection($buffer,  'command', CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }
}

class CloudAdminAPI extends APICore
{
    use MetadataAttributeTypeCasterTrait;
  
    public function __construct($SERVER_URL, $debug = false) {
        parent::__construct($SERVER_URL, $debug);
    }

    public function __destruct() {
        parent::__destruct();
    }
    
    public function getLastRunTime()
    {
        return $this->elapsed();
    }

    public function adminlogin($adminuser, $adminpassword) {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=adminlogin&adminuser=' . $adminuser . '&adminpassword=' . $adminpassword;
        $buffer = $this->doPOSTWithHeader($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }

    /**
     * Update a metadata set definition
     * 
     * @param string $id
     * @param string $name
     * @param string $description
     * @param bool $disabled
     * @param bool $allowAllPaths
     * @param int $type
     * @param array $attributes
     * @param array $users
     * @param array $groups
     * @param array $paths
     * @return CommandRecord|null
     */
    public function updateMetadataSet(
        string $id,
        string $name,
        string $description,
        bool $disabled,
        bool $allowAllPaths,
        int $type,
        array $attributes,
        array $users,
        array $groups,
        array $paths
    ): ?CommandRecord {
        $this->startTimer();
        
        $data = $this->composeMetadataSetDefinitionData(...func_get_args());
        $response = $this->doPOST("{$this->server_url}/admin/updatemetadataset", http_build_query($data));
        $collection = new Collection($response,  "command", CommandRecord::class);
        $records = $collection->getRecords();
        $record = $collection->getNumberOfRecords() > 0 ? reset($records) : null;
        $this->stopTimer();
        
        return $record;
    }

    /**
     * Add a new metadata set definition
     * 
     * @param string $name
     * @param string $description
     * @param bool $disabled
     * @param bool $allowAllPaths
     * @param array $attributes
     * @param array $users
     * @param array $groups
     * @param array $paths
     * @return CommandRecord|null
     */
    public function addMetadataSet(
        string $name,
        string $description,
        bool $disabled,
        bool $allowAllPaths,
        array $attributes,
        array $users,
        array $groups,
        array $paths
    ): ?CommandRecord {
        $this->startTimer();

        $data = $this->composeMetadataSetDefinitionData(
            '',
            $name,
            $description,
            $disabled,
            $allowAllPaths,
            3,  // Only custom set definitions can be created.
            $attributes,
            $users,
            $groups,
            $paths
        );

        $response = $this->doPOST("{$this->server_url}/admin/addmetadataset", http_build_query($data));
        $collection = new Collection($response,  "command", CommandRecord::class);
        $records = $collection->getRecords();
        $record = $collection->getNumberOfRecords() > 0 ? reset($records) : null;
        $this->stopTimer();

        return $record;
    }

    /**
     * Delete specified metadata set
     * 
     * @param string $setId
     * @return CommandRecord|null
     */
    public function deleteMetadataSet(string $setId): ?CommandRecord
    {
        $response = $this->doPOST("{$this->server_url}/admin/deletemetadataset", http_build_query([
            'setid' => $setId
        ]));
        $collection = new Collection($response,  "command", CommandRecord::class);
        $records = $collection->getRecords();
        $record = $collection->getNumberOfRecords() > 0 ? reset($records) : null;
        $this->stopTimer();

        return $record;
    }

    /**
     * Get metadata set definitions.
     * 
     * @param string $keyword
     * @param int $start Starts at 0
     * @param int $end
     * @return Collection
     */
    public function getMetadataSetDefinitions(
        string $keyword = '',
        int $start = 0,
        int $end = 0
    ): Collection {
        $this->startTimer();
        $response = $this->doPOST(
            "{$this->server_url}/admin/getmetadatasetdefinitions",
            http_build_query([
                'keyword' => $keyword,
                'start' => $start,
                'end' => $end,
            ])
        );
        $collection = new Collection(
            $response,
            "metadataset",
            AdminMetadataSetRecord::class
        );
        $this->stopTimer();

        return $collection;
    }

    /**
     * Get a single metadata set by id
     * 
     * @param string $setId
     * @return AdminMetadataSetRecord|null
     */
    public function getMetadataSet(string $setId): ?AdminMetadataSetRecord
    {
        $this->startTimer();
        $response = $this->doPOST(
            "{$this->server_url}/admin/getmetadataset",
            http_build_query([
                'setId' => $setId,
            ])
        );

        $collection = new Collection(
            $response,
            "metadataset",
            AdminMetadataSetRecord::class
        );
        $records = $collection->getRecords();
        $record = $collection->getNumberOfRecords() > 0 ? reset($records) : null;
        $this->stopTimer();

        return $record;
    }

    /**
     * Build the request data
     * 
     * @param string $id
     * @param string $name
     * @param string $description
     * @param bool $disabled
     * @param bool $allowAllPaths
     * @param int $type
     * @param array $attributes
     * @param array $users
     * @param array $groups
     * @param array $paths
     * @return array
     */
    private function composeMetadataSetDefinitionData(
        string $id,
        string $name,
        string $description,
        bool $disabled,
        bool $allowAllPaths,
        int $type,
        array $attributes,
        array $users,
        array $groups,
        array $paths
    ): array {
        // basic fields
        $data = [
            'id' => $id,
            'name' => $name,
            'description' => $description,
            'disabled' => json_encode($disabled),
            'allowallpaths' => json_encode($allowAllPaths),
            'type' => $type
        ];

        // attributes
        $nonStringFields = ['type', 'required', 'disabled'];
        foreach ($attributes as $i => $attribute) {
            foreach ($attribute as $fieldName => $fieldValue) {
                $transformedFieldValue = $fieldValue;
                if (in_array($fieldName, $nonStringFields)) {
                    $transformedFieldValue = json_encode($fieldValue);
                }

                if ($fieldName === 'defaultvalue') {
                    $transformedFieldValue = $this->reverseCastFromType($fieldValue, $data["attribute{$i}_type"]);
                }
                
                if ($fieldName === 'predefinedvalue') {
                    foreach ($fieldValue as $j => $val) {
                        $data["attribute{$i}_predefinedvalue{$j}"] = $val;
                    }
                    
                    continue;
                }

                $data["attribute{$i}_{$fieldName}"] = $transformedFieldValue;
            }
        }
        $data['attributes_total'] = count($attributes);

        // allowed users
        foreach ($users as $i => $user) {
            $data["user{$i}_name"] = $user['name'];
            $data["user{$i}_read"] = json_encode($user['read']);
            $data["user{$i}_write"] = json_encode($user['write']);
        }
        $data['users_total'] = count($users);

        // allowed groups
        foreach ($groups as $i => $group) {
            $data["group{$i}_id"] = $group['id'];
            $data["group{$i}_name"] = $group['name'];
            $data["group{$i}_read"] = json_encode($group['read']);
            $data["group{$i}_write"] = json_encode($group['write']);
        }
        $data['groups_total'] = count($groups);

        // allowed paths
        foreach ($paths as $i => $path) {
            $data["path{$i}"] = $path;
        }
        $data['paths_total'] = count($paths);
        
        return $data;
    }

    /**
     * Returns a list of User objects matching the specified criteria
     *
     * This method does a search of all users and returns users matching the specific pattern
     *
     * @param string $username
     * @return collection
     */
    public function searchUsers($username, $groupidnin="", $externalin="", $status="", $admin="") {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=search&keyword=' . $username . '&groupidnin=&externalin=&status=&statusnin=&start=0&end=10&admin=';
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "user", UserRecord::class, "meta");
        $this->stopTimer();
        return $collection;
    }
    
     //---- SETADMIN STATUS API
    //RETURNS a Command Record
    public function setAdminstatus($profile, $adminstatus) {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=setadminstatus&profile=' . $profile . '&adminstatus=' . $adminstatus;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }

    //---- ADDUSER API
    //RETURNS a Command Record
    public function addUser($username, $email, $password, $authtype, $status) {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=adduser&username=' . $username . '&email=' . $email . '&password=' . $password . '&authtype=' . $authtype . '&status=' .$status;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }

    //---- DELETE USER API
    //RETURNS a Command Record
    public function deleteUser($profile) {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=deleteuser&profile=' . $profile;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }

    public function addgroup($groupname) {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=addgroup&groupname=' . $groupname;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "group", GroupRecord::class, "meta");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
         {
            return $collection->getRecords()[0];
        }
        else if($collection->getMetaRecord() != NULL)
        {
            return $collection;
        }
        else
        {
            return NULL;
        }
    }    

    public function deletegroup($groupId) {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=deletegroup&groupid=' . $groupId;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }
    
    public function addMemberToGroup($groupId, $userId) {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=addmembertogroup&groupid=' . $groupId . '&userid=' . $userId;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }
    
    public function deleteMemberFromGroup($groupId, $userId) {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=deletememberfromgroup&groupid=' . $groupId . '&userid=' . $userId;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }
    
     //API for TRIMAUDITDB
    //---RETURNS a command record
    public function trimAuditdb($enddate,$startdate = "") {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        if($startdate != "" && $startdate != NULL)
        {
            $postdata = 'op=trimauditdb&startdate=' . $startdate . '&enddate='.$enddate;
        }
        else
        {
            $postdata = 'op=trimauditdb&enddate=' . $enddate;
        }
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];

        return NULL;
    }

    //---SET ADMINUSERPOLICY API
    //---RETURNS a command record
    public function setadminUserpolicy($username, $opname, $create = "", $read = "", $update = "", $delete = "") {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        if ($create != "" && $create != null)
            $create = '&create=' . $create;

        if ($read != "" && $read != null)
            $read = '&read=' . $read;

        if ($update != "" && $update != null)
            $update = '&update=' . $update;

        if ($delete != "" && $delete != null)
            $delete = '&delete=' . $delete;

        $postdata = 'op=setadminuserpolicy&username=' . $username . '&opname=' . $opname . $create . $read . $update . $delete;
        ;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];

        return NULL;
    }

    //---API for CLEARALLALERTS
    //---RETURNS a command record
    public function clearallAlerts() {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=clearallalerts';
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];

        return NULL;
    }


    //API to activate encryption using a password
    //---RETURNS a command record
    public function cryptfsActivate($passphrase) {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=cryptfsactivate&passphrase=' . $passphrase;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];

        return NULL;
    }

    //API to encrypt all the files
    //---RETURNS a command record
    public function cryptfsInit($passphrase, $addrecoverykey) {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        //if ($passphrase != "" && $passphrase != null)
           // $passphrase = '&passphrase=' . $passphrase;
        $postdata = 'op=cryptfsinit&passphrase=' . $passphrase . '&addrecoverykey=' . $addrecoverykey;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];

        return NULL;
    }

    //API to encrypt all the files
    //---RETURNS a command record
    public function cryptfsEncryptall() {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=cryptfsencryptall';
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];

        return NULL;
    }

    //API to decrypt all the files
    //---RETURNS a command record
    public function cryptfsDecryptall() {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=cryptfsdecryptall';
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];

        return NULL;
    }

    //API to reset encryption
    //---RETURNS a command record
    public function cryptfsReset() {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=cryptfsreset';
        $buffer = $this->doPOST($url, $postdata);
        return $buffer;
    }

    //API to reset encryption
    //---RETURNS a encryption record
    public function cryptfsStatus() {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=cryptfsstatus';
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "encstatus", EncryptionstatusRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        
        return NULL;
    }
    
    public function cryptfsDownloadRecoveryKey(){
        $this->startTimer();
        $url = $this->server_url . "/admin/?op=cryptfsdownloadrecoverykey";
        $buffer = $this->doGET($url);
        $httpcode = curl_getinfo($this->curl_handle, CURLINFO_HTTP_CODE);
        if ($httpcode=='200' ) {
            
            return $buffer;
        }
        else {
            return false;
        }
 }
    
    public function logout() {
        $this->startTimer();
        $url = $this->server_url . "/admin/?op=logout";
        $postdata = "";
        $buffer = $this->doPOST($url, $postdata);
		//$this->cookie_data = array();
		$this->xsrf_token = "";
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }
    
    public function addExternal($externalname , $location, $automount, $automounttype, $automountparam1, $perm){
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=addexternal&externalname=' . $externalname . '&location='. $location . '&automount=' . $automount . '&automounttype='
                . $automounttype . '&automountparam1=' . $automountparam1 . '&perm=' . $perm  ;
        
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "external", ExternalRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
            return $collection->getRecords()[0];
        }
        else
        {
            return NULL;
        }
    }
    
    public function updateExternal($externalid, $externalname , $location, $automount, $automounttype, $automountparam1, $perm){
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=updateexternal&externalid='. $externalid . '&externalname=' . $externalname . '&location='. $location . '&automount=' . $automount . '&automounttype='
                . $automounttype . '&automountparam1=' . $automountparam1 . '&perm=' . $perm  ;
        
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "external", ExternalRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
            return $collection->getRecords()[0];
        }
        else
        {
            return NULL;
        }
    }
    
    public function addUsertoExternal($writemode ,$externalid ,$userid){
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=addusertoexternal&writemode=' . $writemode .'&externalid='. $externalid . '&userid=' . $userid;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        
        return NULL;    
        }
        
    public function getExternals($filter=''){
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        if($filter != '')
        {
            $postdata = 'op=getexternals&filter='.$filter;
        }
        else
        {
            $postdata = 'op=getexternals';
        }
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "external", ExternalRecord::class, "meta");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection;
        
        return NULL;    
        }
        
    public function addGrouptoExternal($writemode ,$externalid ,$groupid){
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=addgrouptoexternal&writemode=' . $writemode .'&externalid='. $externalid . '&groupid=' . $groupid;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        
        return NULL;    
        }
        
    public function getGroupsForExternal($externalid){
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=getgroupsforexternal&externalid='. $externalid;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "group", GroupListRecord::class, "meta");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
            return $collection;
        }
        else
        {
            return $collection->getMetaRecord();
        }
        return NULL;    
        }
        
    public function getUsersForExternal($externalid){
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=getusersforexternal&externalid='. $externalid;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "user", UserListRecord::class, "meta");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
            return $collection;
        }
        else
        {
            return $collection->getMetaRecord();
        }
        return NULL;    
        }    
        
    public function deleteGroupFromExternal($externalid , $groupid){
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=deletegroupfromexternal&externalid='. $externalid . '&groupid=' . $groupid;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        
        return NULL;    
        }
        
    public function deleteUserFromExternal($externalid , $username){
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=deleteuserfromexternal&externalid='. $externalid . '&userid=' . $username;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        
        return NULL;    
        }
        
    public function deletExternal($externalid){
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=deleteexternal&externalid='. $externalid;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        
        return NULL;    
        }
    
    
    public function updateUserAccessLevel($profilename , $emailid , $status){
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=updateuser&profile='. $profilename .'&email='. $emailid . '&status=' . $status;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        
        return NULL;    
        }
    
    public function allowAccountSignUp($value){
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=setconfigsetting&count=1&param0=TONIDOCLOUD_ACCOUNT_CREATION_MODE&value0=' . $value;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        
        return NULL;    
        }
        
    public function set2fa($value){
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=setconfigsetting&count=1&param0=TONIDOCLOUD_ENABLE_2FA&value0=' . $value;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        
        return NULL;    
        }    
 
    public function setConfigSettings($configconstant, $value){
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=setconfigsetting&count=1&param0='.$configconstant.'&value0=' . $value;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        
        return NULL;    
        }     
        
    public function getConfigSettings($configconstant){
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=getconfigsetting&count=1&param0='.$configconstant;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "setting", ConfigSettingRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        
        return NULL;    
        } 
        
    //API to get config settings using an array
    //RETURNS a Command Record
    public function getConfigSettingsArray($count,$config_array){
        $this->startTimer();
               $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=getconfigsetting&count='. $count;
        $i = 0;
         foreach ($config_array as $const) {
            $postdata .='&param'.$i."=" . $const ;
            $i++;
        }
        $buffer = $this->doPOST($url, $postdata);
        $pos = strpos($buffer, '0');
        if($pos == '56')
        {
            $collection = new Collection($buffer,  "command", CommandRecord::class);
            if ($collection->getNumberOfRecords() > 0)
            {
                return $collection->getRecords()[0];
            }
        }
        
        $collection = new Collection($buffer,  "setting", ConfigSettingRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection;
        
        return NULL;    
        }    
        
    //API clearconfigsetting
    //RETURNS a Command Record
    public function clearConfigSetting($count,$config_array){
        $this->startTimer();
               $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=clearconfigsetting&count='. $count;
        $i = 0;
         foreach ($config_array as $const) {
            $postdata .='&param'.$i."=" . $const ;
            $i++;
        }
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        if ($collection->getNumberOfRecords() > 0)
        {
            return $collection->getRecords()[0];
        }
            return NULL;    
        }    
        

    public function updateBackupPath($profilename , $emailid , $backuppathoverride){
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=updateuser&profile='. $profilename .'&email='. $emailid . '&backuppathoverride=' . $backuppathoverride;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        
        return NULL;    
        }
    //API to set config settings using an array
    //RETURNS a Command Record
    public function setConfigSettingsArray($count,$config_array){
        $this->startTimer();
               $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=setconfigsetting&count='. $count;
        $i = 0;
         foreach ($config_array as $key => $value) {
            $postdata .='&param'.$i."=" . $key . '&value'.$i . "=".$value;
            $i++;
        }
        //echo $postdata;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        
        return NULL;    
        }
    //API to check AD settings
    //RETURNS a Command Record
    public function checkAdLogin() {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=checkadlogin';
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        
        return NULL;
        }
        
    //API to check LDAP settings
    //RETURNS a Command Record
    public function checkLdapLogin() {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=checkldaplogin';
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        
        return NULL;
        }    
        
    //API to check storage settings
    //RETURNS a Command Record
    public function checkStorageSettingforLocal($storagetype, $path) {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=checkstoragesetting&storagetype=' . $storagetype . '&path=' . $path;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        
        return NULL;
        }   
        
    public function checkStorageSettingforOpenStack($storagetype,$opserver, $opport , $opaccount , $opuser, $oppassword) {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=checkstoragesetting&storagetype=' . $storagetype .'&server=' . $opserver . '&port=' . $opport . '&account=' . $opaccount . '&user=' . $opuser . '&password=' .$oppassword;;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        
        return NULL;
        }   
        
    public function checkStorageSettingforAmazonS3($storagetype,$key,$secret,$bucketid,$region,$endpoint,$noofversion) {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=checkstoragesetting&storagetype=' . $storagetype . '&key=' . $key .'&secret=' .$secret . '&bucketid=' . 
                $bucketid . '&noov=' . $noofversion . '&region=' . $region . '&endpoint=' .$endpoint;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        
        return NULL;
        }    
        
    //API to check Clam AV settings
    //RETURNS a Command Record
    public function checkClamAV() {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=checkclamav';
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        
        return NULL;
        }    
        
    //API to check send email
    //RETURNS a Command Record
    public function checkSendEmail() {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=checksendemail';
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer(); 
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        
        return NULL;
        }      
        
    //API to check setting path
    //RETURNS a Command Record
    public function checkSettingPath($path) {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=checksettingpath&path=' . $path ;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer(); 
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        
        return NULL;
        }      
        
    //API to get email id for AD user
    //RETURNS a Command Record
    public function getEmailId($name, $password) {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=getemailid&name=' . $name . '&password=' . $password ;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer(); 
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        
        return NULL;
        }
        
    //API to get email id for LDAP user
    //RETURNS a Command Record
    public function getEmailIdForLdap($name,$password) {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=getemailidforldap&name=' . $name . '&password=' .$password ;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer(); 
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        
        return NULL;
        }   
        
    public function getLicense(){
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=getlicense';
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, 'license', LicenseRecord::class);
        $this->stopTimer(); 
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        
        return NULL;
        
    }
        
    //API to get AD groups
    //RETURNS a Adgroup Record  
    public function getAdGroups(){
         $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=getadgroups';
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "entry", AdgroupRecord::class, "meta");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
            return $collection;
        }
        else
        {
            return $collection->getMetaRecord();
        }
        return NULL;    
        }
    //API to get Group by group name
    //RETURNS a Group Record
    public function getGroupByName($groupName) {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=getgroupbyname&groupname=' . $groupName;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "group", GroupRecord::class, "meta");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            {
            return $collection->getRecords()[0];
            }
        else if($collection->getMetaRecord() != NULL)
            {
            return $collection;
            }
        else
            {
            return NULL;
            }
        }
    //API to update a group
    //RETURNS a Group Record
    public function updateGroup($groupName, $groupId) {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=updategroup&groupname=' . $groupName . '&groupid=' . $groupId;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "group", GroupRecord::class, "meta");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            {
            return $collection->getRecords()[0];
            }
        else if($collection->getMetaRecord() != NULL)
            {
            return $collection;
            }
        else
            {
            return NULL;
            }
        }
        
    //API to get groups
    //RETURNS a Group  Record  
    public function getGroups(){
         $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=getgroups';
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "group", GroupRecord::class , "meta");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
            return $collection;
        }
        else
        {
            return $collection->getMetaRecord();
        }
        return NULL;    
        }
       
    //API to get members of Group
    //RETURNS a Member Record

    /**
     * Returns members of the group page
     *
     * @param string $groupId Group id
     * @param int $start beginning of the page
     * @param int $end end of tha page
     * @return Collection|null
     */
    public function getMembersForGroup(string $groupId, int $start = 0, int $end = 10) {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = http_build_query([
            'op' => 'getmembersforgroup',
            'groupid' => $groupId,
            'start' => $start,
            'end' => $end
        ]);
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "member", MembersRecord::class, "meta");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0) {
            return $collection;
        }
        return NULL;
    }
        
    //API to get admin users 
    //RETURNS AdminUsersRecord
    public function getAdminUsers() {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=getadminusers';
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "adminuser", AdminUsersRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
           {
            return $collection;
           }
        else
            {
            return NULL;
            }   
        }    
     
    // API getadminuserpolicy
    // Returns UserOperationsRecord
    public function getAdminUserPolicy($username) {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=getadminuserpolicy&username='.$username;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "operation", UserOperationsRecord::class, "meta");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
           {
            return $collection;
           }
        else
            {
            return NULL;
            }   
        }    
        
    // API getadminuseroperationpermission
    // Returns Permission Record
    public function getAdminUserOperationPermission($username,$opname) {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=getadminuseroperationpermission&username='.$username.'&opname='.$opname;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "permission", PermissionRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
           {
            return $collection->getRecords()[0];
           }
        else
            {
            return NULL;
            }   
        }        
        
        
    // API getadminoperations
    // Returns UserOperationsRecord
    public function getAdminOperations() {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=getadminoperations';
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "operation", UserOperationsRecord::class, "meta");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
           {
            return $collection;
           }
        else
            {
            return NULL;
            }   
        }    
    // API deleteadminuser
    // Returns CommandRecord   
    public function deleteAdminUser($username) {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=deleteadminuser&username=' . $username;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }    
    //API to get members of AD group
    //RETURNS a Entry Record
        public function getAdGroupMembers($gmgroup){
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=getadgroupmembers&gmgroup=' . $gmgroup;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "entry", AdgroupMemberRecord::class, "meta");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
            return $collection;
        }
        else
        {
            return $collection->getMetaRecord();
        }
        return NULL;
    }


    // API getdoelist
    // Returns UserOperationsRecord
    public function getDoEList() {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=getdoelist';
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "doeitem", DoNotEmailRecord::class, "meta");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
           {
            return $collection;
           }
        else
            {
            return NULL;
            }   
        }
        
    public function clearAllDoeList() {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=clearalldoelist';
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }    
    
    public function removeFromDoeList($rid) {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=removefromdoelist&rid=' . $rid;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }
    
    
    //Setuserpassword
    //Returns Command Record
    public function setUserPassword($username , $password) {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=setuserpassword&profile=' . $username . '&password=' . $password;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }
    
    //Resetpassword
    //Returns Command Record
    public function resetPassword($username ) {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=resetpassword&profile=' . $username ;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }
    
    //GetSharesByOwner
    //Returns Share Record
    public function getSharesByOwner($ownername, $filter = "") {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=getsharesbyowner&shareowner=' . $ownername .'&sharefilter=' .$filter .'&start=0&limit=10';
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "share", ShareRecord::class, "meta");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            {
                return $collection;
            }
        else
            {
                return $collection->getMetaRecord();
            }
    }
    
    //GetUser
    //Returns UserRecord
    public function getUser($username) {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=getuser&username=' . $username ;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "user", UserRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }
    
    //GetUserUsage
    //Returns UserUsageRecord
    public function getUserUsage($username) {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=getuserusage&username=' . $username ;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "usage", UserUsageRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }
        
        
    //API to get latest users added into system
    //RETURNS a ITEM Record
    public function getLatestUsersAdded() {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=getlatestusersadded';
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "user", UserRecord::class);
        $this->stopTimer();  
        if ($collection->getNumberOfRecords() > 0)
        {
            return $collection;
        }
        else
        {
            return $collection->getMetaRecord();
        }
    }
    
    //API to get latest files added into system
    //RETURNS a ITEM Record
    public function getLatestFilesAdded() {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=getlatestfilesadded';
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "item", ItemRecord::class);
        $this->stopTimer();  
        if ($collection->getNumberOfRecords() > 0)
        {
            return $collection->getRecords()[0];
        }
        else
        {
            return $collection->getMetaRecord();
        }
    }
    
    //API to generate test alerts
    //Return 1
    public function generateAlerts()
    {
        $url = $this->server_url . "/app/testhelper/";
        $postdata = "op=generatealerts";
        $buffer = $this->doPOST($url, $postdata);
        return $buffer;
    }
    
    //API to create multi.php file
    //Return True/False
    public function createMultiSiteFile()
    {
        $url = $this->server_url . "/app/testhelper/";
        $postdata = "op=createmultisitefile";
        $buffer = $this->doPOST($url, $postdata);
        return $buffer;
    }
    
    //API getsysalerts
    //Returns AlertsRecord
    public function getSysAlerts() {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=getsysalerts';
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "alert", AlertsRecord::class, "meta");
        $this->stopTimer();  
        if ($collection->getNumberOfRecords() > 0)
        {
            return $collection;
        }
        else
        {
            return $collection->getMetaRecord();
        }
    }
    
    //API removealert
    //Returns Command Record
    public function removeAlert($rid ) {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=removealert&rid=' . $rid ;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }
    
    //API superadminaddsite
    //Returns Command Record
    public function superAdminAddSite($name , $siteurl , $duplicatesitename ) {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=superadminaddsite&name=' . $name . '&url=' . $siteurl . '&duplicatesitename=' . $duplicatesitename;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }
    
    //API superadmineditsite
    //Returns Command Record
    public function superAdminEditSite($name , $siteUrl ) {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=superadmineditsite&name=' . $name . '&url=' . $siteUrl ;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }
    
    //API superadminremovesite
    //Returns Command Record
    public function superAdminRemoveSite($siteurl ) {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=superadminremovesite&url=' . $siteurl ;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }
    
    //API superadminlogin
    //Returns Command Record
    public function superAdminLogin($superadminuser , $superadminpassword ) {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=superadminlogin&superadminuser=' . $superadminuser . '&superadminpassword=' . $superadminpassword ;
        $buffer = $this->doPOSTWithHeader($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }
    
    //API superadmingetallsites
    //Returns AlertsRecord
    public function superAdminGetAllSites() {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=superadmingetallsites';
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "site", SiteRecord::class, "meta");
        $this->stopTimer();  
        if ($collection->getNumberOfRecords() > 0)
        {
            return $collection;
        }
        else
        {
            return $collection->getMetaRecord();
        }
    }
    
    //API superadminlogout
    //Returns Command Record
    public function superAdminLogout( ) {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=superadminlogout';
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }
    
    //API to get config settings using an array
    //RETURNS a Command Record
    public function superAdminGetConfigSetting($count,$siteurl, $config_array){
        $this->startTimer();
               $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=superadmingetconfigsetting&count='. $count . '&sitehostname=' .$siteurl;
        $i = 0;
         foreach ($config_array as $const) {
            $postdata .='&param'.$i."=" . $const ;
            $i++;
        }
        $buffer = $this->doPOST($url, $postdata);
        $pos = strpos($buffer, '0');
        if($pos == '66')
        {
            $collection = new Collection($buffer,  "command", CommandRecord::class);
            if ($collection->getNumberOfRecords() > 0)
            {
                return $collection->getRecords()[0];
            }
        }
        
        $collection = new Collection($buffer,  "setting", ConfigSettingRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection;
        
        return NULL;    
        }
        
    //API to set config settings using an array
    //RETURNS a Command Record
    public function superAdminSetConfigSettings($count,$siteurl,$config_array){
        $this->startTimer();
               $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=superadminsetconfigsetting&count='. $count. '&sitehostname=' .$siteurl;
        $i = 0;
         foreach ($config_array as $key => $value) {
            $postdata .='&param'.$i."=" . $key . '&value'.$i . "=".$value;
            $i++;
        }
        //echo $postdata;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        
        return NULL;    
        }  
        
    //API superadminauthstatus
    //Returns Command Record
    public function superAdminAuthStatus( ) {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=superadminauthstatus';
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }

    
    //API getaudit
    //Returns AuditRecord
    public function getAudit($username = "" , $operation = "" , $startdate = "" , $enddate = "", $sortfield = "", $sortdir = "") {
        $this->startTimer();
        $url = $this->server_url . '/admin/?op=getaudit';
        if($username != "")
        {
            $url .= '&username=' .  $username;
        }
        if($operation != "")
        {
            $url .= '&operation=' .$operation;
        }
        if($startdate != "")
        {
            $url .= '&startdate=' .$startdate; 
        }
        if($enddate != "")
        {
            $url .= '&enddate='.$enddate;
        }
        if($sortfield != "")
        {
            $url .= '&sortfield=' . $sortfield;
        }
        if($sortdir != "")
        {
            $url .= '&sortdir=' . $sortdir;
        }
        $buffer = $this->doGET($url);
        $collection = new Collection($buffer,  "log", AuditRecord::class, "meta");
        $this->stopTimer();  
        if ($collection->getNumberOfRecords() > 0)
        {
            return $collection;
        }
        else
        {
            return $collection->getMetaRecord();
        }
    }
    
    //API exportAudit
    //Returns csv file
    public function exportAudit($enddate , $savepath , $startdate = "" )
    {
        $this->startTimer();
        if($startdate != "" && $startdate != NULL)
        {
            $url = $this->server_url . '/admin/index.php/?op=exportaudit&startdate=' .$startdate . '&enddate='.$enddate;
        }
        else
        {
            $url = $this->server_url . '/admin/index.php/?op=exportaudit&enddate='.$enddate;
        }
        $buffer = $this->doGET($url);
        $httpcode = curl_getinfo($this->curl_handle, CURLINFO_HTTP_CODE);
	if ($httpcode=='200' ) {
	    $fp = fopen($savepath,'w');
            $filesize = fwrite($fp,$buffer);			
            fclose($fp);			
            return true;
	}
	else {
            return false;
	}
    }
    
    //API generateffdc
    //Return a zip file
    public function generateFFDC($savepath)
    {
        $url = $this->server_url . '/admin/index.php/?op=generateffdc';
        $zipfile = $this->doGET($url);
        $httpcode = curl_getinfo($this->curl_handle, CURLINFO_HTTP_CODE);
	if ($httpcode=='200'&& $zipfile != NULL) {
            $fp = fopen($savepath,'w');
            $filesize = fwrite($fp,$zipfile);			
            fclose($fp);
	    return true;
	}
	else {
            return false;
	}
    }
    
     //API to update user
    //Returns a Command Record
    public function updateUser($profile, $size, $status, $verified, $email, $localuser, $displayname, 
            $expirationdate, $adminstatus, $sharemode, $disablemyfilessync, $disablenetworksync, $backuppathoverride ){
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=updateuser&profile=' . $profile . '&size=' . $size .
                '&status=' . $status  . '&verified=' . $verified  . '&email=' . $email . '&localuser=' . $localuser .   
                '&displayname=' . $displayname . '&expirationdate=' . $expirationdate .   
                '&adminstatus=' . $adminstatus . '&sharemode=' . $sharemode .
                '&disablemyfilessync=' . $disablemyfilessync . 
                '&disablenetworksync=' . $disablenetworksync . '&backuppathoverride=' . $backuppathoverride;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }
    //API to importadgroup
    //Returns a groupimport Record
    public function importAdGroup($groupname, $groupid, $autosync){
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=importadgroup&groupname=' . $groupname . '&groupid=' . $groupid .
                '&autosync=' . $autosync;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "groupimport", AdGroupImportRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        if ($collection->getNumberOfRecords() > 0)
        {
            $this->stopTimer();
            return $collection->getRecords()[0];
        }
        return NULL;
    }
    
    public function twofaAdminLogin($adminuser, $token, $code)
    {
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=2falogin&adminuser=' . $adminuser . '&token=' . $token . '&code=' .$code;
        $buffer = $this->doPOSTWithHeader($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        if ($collection->getNumberOfRecords() > 0)
        {
            $this->stopTimer();
            return $collection->getRecords()[0];
        }
        return NULL;
    }
    
    public function twofaAdminCode($issuperadmin)
    {
        $this->startTimer();
        $url = $this->server_url . "/app/testhelper/";
        $postdata = "op=twofaadmincode&issuperadmin=".$issuperadmin ;
        $buffer = $this->doPOST($url, $postdata);
        $this->stopTimer();
        return $buffer;
    }
    
    public function clearAllConfigSetting()
    {
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=clearallconfigsetting';
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        if ($collection->getNumberOfRecords() > 0)
        {
            $this->stopTimer();
            return $collection->getRecords()[0];
        }
        return NULL;
    }
    
    //API To empty networkshare recyclebin
    public function emptyNetworkFolderRecyclebin($location){
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=emptynetworkfolderrecyclebin&location=' . $location;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }
    
    public function setLicense($filearray)
    {
        $url = $this->server_url . "/admin/index.php/?op=setlicensexml&param=INSTALL_LICENSE";
        $buffer = $this->doLicenseUpload($url, $filearray);
        return $buffer;
    }
      
    //API getrmcclient
    //Returns RMC record
    public function getRmcClients($username = '', $status = ''){
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        if($username != '' && $status != '')
        {
            $postdata = 'op=getrmcclients&userid=' . $username . '&status=' . $status ;
        }
        elseif($status == '' && $username != '')
        {
            $postdata = 'op=getrmcclients&userid=' . $username ;
        }
        elseif($status != '' && $username == '' )
        {
            $postdata = 'op=getrmcclients&status=' . $status ;
        }
        else
        {
            $postdata = 'op=getrmcclients&start=0&end=10';
        }
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "rmc_client", RMCRecord::class, "meta");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
            return $collection;
        }
        else
        {
            return $collection->getMetaRecord();
        }
        return NULL;    
    }
    
    //API To removermcclient
    public function removeRmcClient($clientid){
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=removermcclient&remote_client_id=' . $clientid;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }
    
    //API To addrmccommand
    public function addRmcCommand($clientid, $commandid, $message){
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=addrmccommand&remote_client_id=' . $clientid . '&remote_command_id=' . $commandid . '&message=' .$message;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }
    
    //API getrmccommands
    //Returns RMC record
    public function getRmcCommands($clientid){
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=getrmccommands&remote_client_id=' . $clientid ;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "rmc_command", RMCCommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
            return $collection;
        }
        else
        {
            return NULL; 
        }
           
    }
    
    //API To removermccommand
    public function removeRmcCommand($rid){
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=removermccommand&remote_command_record_id=' . $rid ;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }
    
    //API To getversion
    public function getVersion(){
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=getversion&force=1' ;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "version", FCVersionRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
            return $collection->getRecords()[0];
        return NULL;
    }
    
    public function getStats()
    {
        $this->startTimer();
        $url = $this->server_url . "/admin/";
        $postdata = 'op=getstats';
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "stat", StatsRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
            return $collection->getRecords()[0];
        }
        return NULL;  
    }
    
    public function import($path)
    {
        $this->startTimer();
        $url = $this->server_url . "/admin/";
        $postdata = 'op=import&path=' .$path;
        $buffer = $this->doPOST($url, $postdata);
        $this->stopTimer();
        return $buffer;    
    }
    
    public function getAuthStatus()
    {
        $this->startTimer();
        $url = $this->server_url . "/admin/";
        $postdata = 'op=getauthstatus';
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
            return $collection->getRecords()[0];
        }
        return NULL; 
    }
    
    public function getInfo()
    {
        $this->startTimer();
        $url = $this->server_url . "/admin/";
        $postdata = 'op=getinfo';
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "info", InfoRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
            return $collection->getRecords()[0];
        }
        return NULL; 
    }
    
    public function getAdminLanguageList()
    {
        $this->startTimer();
        $url = $this->server_url . "/admin/";
        $postdata = 'op=getadminlanguagelist';
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "language", LanguageRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
            return $collection->getRecords()[0];
        }
        return NULL; 
    }
    
    public function csvImport($filearray)
    {
    
        $url = $this->server_url . "/admin/index.php/?op=import";
        $buffer = $this->doLicenseUpload($url, $filearray);
        return $buffer;
       
    }
    
    
    public function twofaSuperAdminLogin($superadminuser, $token, $code)
    {
        
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=2fasuperadminlogin&superadminuser=' . $superadminuser . '&token=' . $token . '&code=' .$code;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        if ($collection->getNumberOfRecords() > 0)
        {
            $this->stopTimer();
            return $collection->getRecords()[0];
        }
        return NULL;
    }
    
    public function downloadImportResult($savepath, $filename)
    {
      
        //. '&downloadedimportfile=' . $downloadedimportfile;
    $filedata = $this->doGET($url);
    $httpcode = curl_getinfo($this->curl_handle, CURLINFO_HTTP_CODE);
    if ($httpcode=='200' ) {
        $fp = fopen($savepath,'w');
               $filesize = fwrite($fp,$filedata);   
               fclose($fp);   
               return true;
    }
    else {
               return false;
    }
    
    }
    
    public function hideGsWizard()
    {
        $this->startTimer();
        $url = $this->server_url . "/admin/";
        $postdata = 'op=hidegswizard';
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
            return $collection->getRecords()[0];
        }
        return NULL; 
    }
    
    public function setConfigSettingXML($param,$data)
    {
    
        $url = $this->server_url . "/admin/index.php/?op=setconfigsettingxml&param=".$param;
        $buffer = $this->doPOST($url, $data);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        if ($collection->getNumberOfRecords() > 0)
        {
            return $collection->getRecords()[0];
        }
        return NULL;
       
    }
    
        
    public function addS3External($externalname, $type, $bucket, $region,
            $endpoint, $key, $secret, $enableenc, $kmsid){
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=addexternal&externalname=' . $externalname .'&type=' .
                $type . '&bucket=' . $bucket . '&region=' . $region .
                '&endpoint=' . $endpoint . '&key=' . $key . '&secret=' .
                $secret . '&enableenc=' . $enableenc . '&kmsid=' .
                $kmsid ;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "external", ExternalRecordForS3Share::class);
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0)
        {
            return $collection->getRecords()[0];
        }
        else
        {
            return NULL;
        }
    
    }
    
    public function addWorkflow($workflowname, $conditionid, $conditionparamjson,
            $actionid,$actionparamjson){
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=addworkflow&workflowname=' . $workflowname .'&conditionid=' .
                $conditionid . '&conditionparamjson=' . $conditionparamjson . '&actionid=' . 
                $actionid .'&actionparamjson=' . $actionparamjson;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        if ($collection->getNumberOfRecords() > 0)
        {
            return $collection->getRecords()[0];
        }
        return NULL;
        }  
        
    public function removeWorkflow($workflowid){
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=removeworkflow&workflowid=' . $workflowid;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        if ($collection->getNumberOfRecords() > 0)
        {
            return $collection->getRecords()[0];
        }
        return NULL;
        }   
    public function getWorkflows($start, $end){
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=getworkflows&start=' . $start . '&limit=' . $end;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "workflow", GetWorkFlowRecords::class, "meta");
        if ($collection->getNumberOfRecords() > 0)
        {
            return $collection;
        }
        else
        {
            return NULL; 
        }
    }
    
    public function updateWorkflow($workflowid, $workflowname, $conditionid, $conditionparamjson,
            $actionid,$actionparamjson){
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=addworkflow&workflowid=' . $workflowid .'&workflowname=' . $workflowname .'&conditionid=' .
                $conditionid . '&conditionparamjson=' . $conditionparamjson . '&actionid=' . 
                $actionid .'&actionparamjson=' . $actionparamjson;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        if ($collection->getNumberOfRecords() > 0)
        {
            return $collection->getRecords()[0];
        }
        return NULL;
        }
    
    public function getConditions(){
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=getconditions';
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "condition", WorkFlowConditionRecords::class);
        if ($collection->getNumberOfRecords() > 0)
        {
            return $collection;
        }
        else
        {
            return NULL; 
        }
    }
    
    public function getActions($forcondition){
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=getactions&forcondition=' . $forcondition;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "action", WorkFlowConditionRecords::class);
        if ($collection->getNumberOfRecords() > 0)
        {
            return $collection;
        }
        else
        {
            return NULL; 
        }
    }    
    
    public function getpolicyforuser($username)
    {
        
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=getpolicyforuser&username=' . $username;
        $buffer = $this->doPOST($url, $postdata);
//        $pos = strpos($buffer, '0');
//        if($pos == '56')
//        {
//            $collection = new Collection($buffer,  "command", CommandRecord::class);
//            if ($collection->getNumberOfRecords() > 0)
//            {
//                return $collection->getRecords()[0];
//            }
//        }
//        else
//        {
          $collection = new Collection($buffer,  "policy", PolicyForUserRecord::class);
            if ($collection->getNumberOfRecords() > 0)
            {
                return $collection;
            }
            else
            {
                return NULL;
            } 
    }
    
    public function updatepolicyforuser($username, $policy, $value)
    {
        
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=updatepolicyforuser&username=' . $username . '&' . $policy . '=' . $value;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        if ($collection->getNumberOfRecords() > 0)
         {
            return $collection->getRecords()[0];
         }
        return NULL;
    }
    
    public function getGroupsForUser($username, int $pageStart = 0, int $pageEnd = 10){
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = http_build_query([
            'op' => 'getgroupsforuser',
            'username' => $username,
            'start' => $pageStart,
            'end' => $pageEnd
        ]);
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "group", GroupRecord::class , "meta");
        $this->stopTimer();
        if ($collection->getNumberOfRecords() > 0) {
            return $collection;
        }
        return $collection->getMetaRecord();
    }
    
    public function setCheckList($param, $value)
    {
        
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=setchecklist&param=' . $param . '&xmlstr=' . $value;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        if ($collection->getNumberOfRecords() > 0)
         {
            return $collection->getRecords()[0];
         }
        return NULL;
    }
    
    public function getCheckList()
    {
        
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=getchecklist';
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "items", CheckListRecord::class, 'meta');
        if ($collection->getNumberOfRecords() > 0)
         {
            return $collection;
         }
        return NULL;
    }
    
    public function getConfigSettingXML($param){
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=getconfigsettingxml&param='.$param;
        $buffer = $this->doPOST($url, $postdata);
        return $buffer;    
    } 
    
    public function getAvailableReportQueries()
    {
        
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=getavailablereportqueries';
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "querytemplate", QueriesRecord::class);
        if ($collection->getNumberOfRecords() > 0)
         {
            return $collection;
         }
        return NULL;
    }
    
    public function addReport($reportname, $reportqueryid, $reportqueryparamjson = "")
    {
        
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=addreport&reportname=' . $reportname . '&reportqueryid=' .
                $reportqueryid . '&reportqueryparamjson=' . $reportqueryparamjson;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        if ($collection->getNumberOfRecords() > 0)
         {
            return $collection->getRecords()[0];
         }
        return NULL;
    }
    
    public function getReports()
    {
        
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=getreports&start=0&limit=10';
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer, "savedreport", ReportsRecord::class,"meta");
        if ($collection->getNumberOfRecords() > 0)
        {
            return $collection;
        }
        else
        {
            return $collection->getMetaRecord();
        }
        return NULL;
    }
    
    public function removeReport($reportid)
    {
        
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=removereport&reportid=' . $reportid;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        if ($collection->getNumberOfRecords() > 0)
         {
            return $collection->getRecords()[0];
         }
        return NULL;
    }
    
    public function updateReport($reportid, $reportqueryid, $reportname, $reportqueryparamjson = "")
    {
        
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=updatereport&reportid=' . $reportid . '&reportqueryid=' .
                $reportqueryid . '&reportname=' . $reportname .
                '&reportqueryparamjson=' . $reportqueryparamjson;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        if ($collection->getNumberOfRecords() > 0)
         {
            return $collection->getRecords()[0];
         }
        return NULL;
    }
    public function checkServerUrl($urlpath){
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=checkserverurl&urlpath=' . $urlpath;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        if ($collection->getNumberOfRecords() > 0)
            {
            return $collection->getRecords()[0];
        }
        return NULL;
        }
    
   public function setTeamFolderUser($username){
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=setteamfolderuser&username=' . $username;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        if ($collection->getNumberOfRecords() > 0)
            {
            return $collection->getRecords()[0];
        }
        return NULL;
        }
        
    public function getTeamFolderProperties(){
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=getteamfolderproperties';
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "teamfolderproperty", TeamfolderpropertiesRecord::class);
        if ($collection->getNumberOfRecords() > 0)
            {
            return $collection->getRecords()[0];
        }
        return NULL;
        }    
    
    
    public function getAllPolicies($policy_name_filter = "")
    {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=getallpolicies&start=0&limit=10&policynamefilter='.$policy_name_filter;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "policy", PolicyRecord::class);
        if ($collection->getNumberOfRecords() > 0)
            {
            return $collection;
        }
        return NULL;
        
    }
    
    public function updatePolicy($policyid,$config_array)
    {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=updatepolicy&policyid='.$policyid;
         foreach ($config_array as $key => $value) {
            $postdata .= "&".$key. "=".$value;
        }
        //echo $postdata;
        $buffer = $this->doPOST($url, $postdata);
        
        $collection = new Collection($buffer,  "command", CommandRecord::class);
        if ($collection->getNumberOfRecords() > 0)
            {
            return $collection->getRecords()[0];
        }
        return NULL;
        
    }
    
    public function getEffectivePolicyForUser($username)
    {
        $this->startTimer();
        $url = $this->server_url . "/admin/index.php";
        $postdata = 'op=geteffectivepolicyforuser&username='.$username;
        $buffer = $this->doPOST($url, $postdata);
        $collection = new Collection($buffer,  "policy", PolicyRecord::class);
        if ($collection->getNumberOfRecords() > 0)
            {
            return $collection;
        }
        return NULL;
        
    }
}
