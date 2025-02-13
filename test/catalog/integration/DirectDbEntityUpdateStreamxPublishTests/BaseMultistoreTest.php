<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

/**
 * {@inheritdoc}
 */
abstract class BaseMultistoreTest extends BaseDirectDbEntityUpdateTest {

    protected const DEFAULT_WEBSITE_ID = 1;
    protected const DEFAULT_STORE_ID = 0;
    protected const STORE_1_ID = 1;
    protected const STORE_2_ID = 2; // manually created according to instructions in the class comments

    protected const SECOND_WEBSITE_ID = 2; // manually created according to instructions in the class comments
    protected const SECOND_WEBSITE_STORE_ID = 3; // manually created according to instructions in the class comments
}