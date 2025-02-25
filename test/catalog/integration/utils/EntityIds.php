<?php

namespace StreamX\ConnectorCatalog\test\integration\utils;

class EntityIds {

    private int $entityId;

    /**
     * either row_id (enterprise/cloud version) or entity_id (community version)
     */
    private int $linkFieldId;

    public function __construct(int $entityId, int $linkFieldId) {
        $this->entityId = $entityId;
        $this->linkFieldId = $linkFieldId;
    }

    public function getEntityId(): int {
        return $this->entityId;
    }

    public function getLinkFieldId(): int {
        return $this->linkFieldId;
    }

}