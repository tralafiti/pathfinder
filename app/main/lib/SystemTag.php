<?php

namespace lib;

use lib\SystemTag\SystemTagInterface;
use Model\Pathfinder\MapModel;
use Model\Pathfinder\SystemModel;

class SystemTag {

    /**
     * @param SystemModel $targetSystem
     * @param SystemModel $sourceSystem
     * @param MapModel $map
     * @return string|null
     */
    static function generateFor(SystemModel $targetSystem, SystemModel $sourceSystem, MapModel $map) : ?string
    {
        $config = Config::getPathfinderData('systemtag');

        if(!isset($config['STATUS']) || $config['STATUS'] !== 1) {
            return null;
        }

        $style = isset($config['STYLE']) ? $config['STYLE'] : 'countConnections';
        $className = '\\lib\\SystemTag\\' . ucfirst($style);

        if(!class_exists($className) || !is_subclass_of($className, SystemTagInterface::class)) {
            return null;
        }

        return SystemTag\CountConnections::generateFor($targetSystem, $sourceSystem, $map);
    }
}