<?php

namespace lib\SystemTag;

use Model\Pathfinder\MapModel;
use Model\Pathfinder\SystemModel;

interface SystemTagInterface {

    /**
     * @param SystemModel $targetSystem
     * @param SystemModel $sourceSystem
     * @param MapModel $map
     * @return string|null
     */
    static function generateFor(SystemModel $targetSystem, SystemModel $sourceSystem, MapModel $map) : ?string;
}