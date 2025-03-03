<?php

namespace StreamX\ConnectorCatalog\test\integration\utils;

class EntityIdsAndName {

    private EntityIds $entityIds;
    private string $name;

    public function __construct(EntityIds $entityIds, string $name) {
        $this->entityIds = $entityIds;
        $this->name = $name;
    }

    public function getEntityIds(): EntityIds {
        return $this->entityIds;
    }

    public function getName(): string {
        return $this->name;
    }

}