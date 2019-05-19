<?php

namespace lib\SystemTag;

use Model\Pathfinder\MapModel;
use Model\Pathfinder\SystemModel;

class CountConnections implements SystemTagInterface
{
    /**
     * @param SystemModel $targetSystem
     * @param SystemModel $sourceSystem
     * @param MapModel $map
     * @return string|null
     */
    static function generateFor(SystemModel $targetSystem, SystemModel $sourceSystem, MapModel $map) : ?string
    {
        $whConnections = array_filter($sourceSystem->getConnections(), function (\Model\Pathfinder\ConnectionModel $connection) { return $connection->isWormhole(); });
        $countWhConnections = count($whConnections);

        // If the source system is a wormhole and isn't locked (aka your home) we need to subtract one incoming connection
        if($sourceSystem->isWormhole() && !$sourceSystem->locked) {
            $countWhConnections = max($countWhConnections - 1, 0);
        }

        return $countWhConnections + 1;
    }
}
