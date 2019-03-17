<?php
/**
 * Created by PhpStorm.
 * User: exodus4d
 * Date: 14.03.15
 * Time: 21:04
 */

namespace Model;

use DB\SQL\Schema;

class SystemFactionKillModel extends AbstractSystemApiBasicModel {

    protected $table = 'system_kills_factions';

    protected $fieldConf = [
        'active' => [
            'type' => Schema::DT_BOOL,
            'nullable' => false,
            'default' => 1,
            'index' => true
        ],
        'systemId' => [
            'type' => Schema::DT_INT,
            'index' => true,
            'unique' => true
        ]
    ];
}