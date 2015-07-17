<?php

class ServiceSubscription extends eZPersistentObject {

    function __construct($row) {
        $this->eZPersistentObject($row);
    }

    public static function definition() {
        static $def = array(
            "fields" => array(
                'id' => array('name' => 'Id',
                    'datatype' => 'integer',
                    'default' => 0,
                    'required' => true),
                'user_id' => array('name' => 'user_id',
                    'datatype' => 'integer',
                    'default' => 0,
                    'required' => false),
                'service_link_id' => array('name' => 'service_link_id',
                    'datatype' => 'integer',
                    'default' => 0,
                    'required' => false),
            ),
            'keys' => array('id'),
            'increment_key' => 'id',
            'class_name' => 'ServiceSubscription',
            'name' => 'service_subscription',
        );
        return $def;
    }

    public static function create(array $row = array()) {
        $object = new self($row);
        return $object;
    }

    public static function fetchByUserId($userId, $asObject = true) { 
        return eZPersistentObject::fetchObjectList( self::definition(), null, array("user_id" => $userId),  array(), null, $asObject,  null, null );
    }
    
    public static function fetchByUserAndServiceLink($userId, $serviceLinkId, $asObject = true) {
        return eZPersistentObject::fetchObject(self::definition(), null, array("user_id" => $userId, "service_link_id" => $serviceLinkId), $asObject);
    }

    static function update($cond, $update_fields) {
        return eZPersistentObject::updateObjectList(array(
                    "definition" => self::definition(),
                    "update_fields" => $update_fields,
                    "conditions" => $cond
                        )
        );
    }

}
