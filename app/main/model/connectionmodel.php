<?php
/**
 * Created by PhpStorm.
 * User: exodus4d
 * Date: 26.02.15
 * Time: 21:12
 */

namespace Model;

use DB\SQL\Schema;
use Controller\Api\Route;
use lib\logging;

class ConnectionModel extends AbstractMapTrackingModel {

    protected $table = 'connection';

    protected $fieldConf = [
        'active' => [
            'type' => Schema::DT_BOOL,
            'nullable' => false,
            'default' => 1,
            'index' => true
        ],
        'mapId' => [
            'type' => Schema::DT_INT,
            'index' => true,
            'belongs-to-one' => 'Model\MapModel',
            'constraint' => [
                [
                    'table' => 'map',
                    'on-delete' => 'CASCADE'
                ]
            ]
        ],
        'source' => [
            'type' => Schema::DT_INT,
            'index' => true,
            'belongs-to-one' => 'Model\SystemModel',
            'constraint' => [
                [
                    'table' => 'system',
                    'on-delete' => 'CASCADE'
                ]
            ],
            'activity-log' => true
        ],
        'target' => [
            'type' => Schema::DT_INT,
            'index' => true,
            'belongs-to-one' => 'Model\SystemModel',
            'constraint' => [
                [
                    'table' => 'system',
                    'on-delete' => 'CASCADE'
                ]
            ],
            'activity-log' =>  true
        ],
        'scope' => [
            'type' => Schema::DT_VARCHAR128,
            'nullable' => false,
            'default' => '',
            'activity-log' => true
        ],
        'type' => [
            'type' => self::DT_JSON,
            'activity-log' => true
        ],
        'eolUpdated' => [
            'type' => Schema::DT_TIMESTAMP,
            'default' => null
        ],
        'signatures' => [
            'has-many' => ['Model\SystemSignatureModel', 'connectionId']
        ],
        'connectionLog' => [
            'has-many' => ['Model\ConnectionLogModel', 'connectionId']
        ]
    ];

    /**
     * get connection data
     * @param bool $addSignatureData
     * @param bool $addLogData
     * @return \stdClass
     */
    public function getData($addSignatureData = false, $addLogData = false){
        $connectionData = (object) [];
        $connectionData->id             = $this->id;
        $connectionData->source         = $this->source->id;
        $connectionData->target         = $this->target->id;
        $connectionData->scope          = $this->scope;
        $connectionData->type           = $this->type;
        $connectionData->updated        = strtotime($this->updated);
        $connectionData->created        = strtotime($this->created);
        $connectionData->eolUpdated     = strtotime($this->eolUpdated);

        if($addSignatureData){
            if( !empty($signaturesData = $this->getSignaturesData()) ){
                $connectionData->signatures = $signaturesData;
            }
        }

        if($addLogData){
            if( !empty($logsData = $this->getLogsData()) ){
                $connectionData->logs = $logsData;
            }
        }

        return $connectionData;
    }

    /**
     * setter for connection type
     * @param $type
     * @return int|number
     */
    public function set_type($type){
        $newTypes = (array)$type;

        // set EOL timestamp
        if( !in_array('wh_eol', $newTypes) ){
            $this->eolUpdated = null;
        }elseif(
            in_array('wh_eol', $newTypes) &&
            !in_array('wh_eol', (array)$this->type) // $this->type == null for new connection! (e.g. map import)
        ){
            // connection EOL status change
            $this->touch('eolUpdated');
        }

        return $type;
    }

    /**
     * check object for model access
     * @param CharacterModel $characterModel
     * @return bool
     */
    public function hasAccess(CharacterModel $characterModel) : bool {
        $access = false;
        if( !$this->dry() ){
            $access = $this->mapId->hasAccess($characterModel);
        }
        return $access;
    }

    /**
     * set default connection type by search route between endpoints
     * @throws \Exception
     */
    public function setDefaultTypeData(){
        if(
            is_object($this->source) &&
            is_object($this->target)
        ){
            if(
                $this->source->isAbyss() ||
                $this->target->isAbyss()
            ){
                $this->scope = 'abyssal';
                $this->type = ['abyssal'];
            }else{
                $routeController = new Route();
                $route = $routeController->searchRoute($this->source->systemId, $this->target->systemId, 1);

                if($route['routePossible']){
                    $this->scope = 'stargate';
                    $this->type = ['stargate'];
                }else{
                    $this->scope = 'wh';
                    $this->type = ['wh_fresh'];
                }
            }
        }
    }

    /**
     * check whether this connection is a wormhole or not
     * @return bool
     */
    public function isWormhole(){
        return ($this->scope === 'wh');
    }

    /**
     * check whether this model is valid or not
     * @return bool
     * @throws \Exception\DatabaseException
     */
    public function isValid(): bool {
        if($valid = parent::isValid()){
            // check if source/target system are not equal
            // check if source/target belong to same map
            if(
                is_object($this->source) &&
                is_object($this->target) &&
                $this->get('source', true) === $this->get('target', true) ||
                $this->source->get('mapId', true) !== $this->target->get('mapId', true)
            ){
                $valid = false;
            }
        }

        return $valid;
    }

    /**
     *  Event "Hook" function
     * can be overwritten
     * return false will stop any further action
     * @param BasicModel $self
     * @param $pkeys
     * @return bool
     * @throws \Exception\DatabaseException
     */
    public function beforeInsertEvent($self, $pkeys){
        // check for "default" connection type and add them if missing
        // -> get() with "true" returns RAW data! important for JSON table column check!
        $types = (array)json_decode($this->get('type', true));
        if(
            !$this->scope ||
            empty($types)
        ){
            $this->setDefaultTypeData();
        }

        return $this->isValid() ? parent::beforeInsertEvent($self, $pkeys) : false;
    }

    /**
     * Event "Hook" function
     * return false will stop any further action
     * @param self $self
     * @param $pkeys
     */
    public function afterInsertEvent($self, $pkeys){
        $self->clearCacheData();
        $self->logActivity('connectionCreate');
    }

    /**
     * Event "Hook" function
     * return false will stop any further action
     * @param self $self
     * @param $pkeys
     */
    public function afterUpdateEvent($self, $pkeys){
        $self->clearCacheData();
        $self->logActivity('connectionUpdate');
    }

    /**
     * Event "Hook" function
     * can be overwritten
     * @param self $self
     * @param $pkeys
     */
    public function afterEraseEvent($self, $pkeys){
        $self->clearCacheData();
        $self->logActivity('connectionDelete');
    }

    /**
     * @param string $action
     * @return logging\LogInterface
     * @throws \Exception\ConfigException
     */
    public function newLog($action = ''): Logging\LogInterface{
        return $this->getMap()->newLog($action)->setTempData($this->getLogObjectData());
    }

    /**
     * @return MapModel
     */
    public function getMap(): MapModel{
        return $this->get('mapId');
    }

    /**
     * delete a connection
     * @param CharacterModel $characterModel
     */
    public function delete(CharacterModel $characterModel){
        if( !$this->dry() ){
            // check if character has access
            if($this->hasAccess($characterModel)){
                $this->erase();
            }
        }
    }

    /**
     * get object relevant data for model log
     * @return array
     */
    public function getLogObjectData() : array{
        return [
            'objId' => $this->_id,
            'objName' => $this->scope
        ];
    }

    /**
     * see parent
     */
    public function clearCacheData(){
        $this->mapId->clearCacheData();
    }

    /**
     * get all signatures that are connected with this connection
     * @return array|mixed
     */
    public function getSignatures(){
        $signatures = [];
        $this->filter('signatures', [
            'active = :active',
            ':active' => 1
        ]);

        if($this->signatures){
            $signatures = $this->signatures;
        }

        return $signatures;
    }

    /**
     * get all jump logs that are connected with this connection
     * @return array|mixed
     */
    public function getLogs(){
        $logs = [];

        if($this->connectionLog){
            $logs = $this->connectionLog;
        }

        return $logs;
    }

    /**
     * get all signature data linked to this connection
     * @return array
     */
    public function getSignaturesData() : array {
        $signaturesData = [];
        $signatures = $this->getSignatures();

        foreach($signatures as $signature){
            $signaturesData[] = $signature->getData();
        }

        return $signaturesData;
    }

    /**
     * get all connection log data linked to this connection
     * @return array
     */
    public function getLogsData() : array {
        $logsData = [];
        $logs = $this->getLogs();

        foreach($logs as $log){
            $logsData[] = $log->getData();
        }

        return $logsData;
    }

    /**
     * get blank connectionLog model
     * @return ConnectionLogModel
     * @throws \Exception
     */
    public function getNewLog() : ConnectionLogModel {
        /**
         * @var $log ConnectionLogModel
         */
        $log = self::getNew('ConnectionLogModel');
        $log->connectionId = $this;
        return $log;
    }

    /**
     * log new mass for this connection
     * @param CharacterLogModel $characterLog
     * @return ConnectionModel
     * @throws \Exception
     */
    public function logMass(CharacterLogModel $characterLog) : self {
        if( !$characterLog->dry() ){
            $log = $this->getNewLog();
            $log->shipTypeId = $characterLog->shipTypeId;
            $log->shipTypeName = $characterLog->shipTypeName;
            $log->shipMass = $characterLog->shipMass;
            $log->characterId = $characterLog->characterId->_id;
            $log->characterName = $characterLog->characterId->name;
            $log->save();
        }

        return $this;
    }

    /**
     * overwrites parent
     * @param null $db
     * @param null $table
     * @param null $fields
     * @return bool
     * @throws \Exception
     */
    public static function setup($db=null, $table=null, $fields=null){
        $status = parent::setup($db,$table,$fields);

        if($status === true){
            $status = parent::setMultiColumnIndex(['source', 'target', 'scope']);
        }

        return $status;
    }
} 