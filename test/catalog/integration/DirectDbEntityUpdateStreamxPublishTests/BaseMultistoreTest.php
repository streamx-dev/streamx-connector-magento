<?php

namespace StreamX\ConnectorCatalog\test\integration\DirectDbEntityUpdateStreamxPublishTests;

/**
 * {@inheritdoc}
 *
 * Note: see StoresControllerImpl for additional stores and website created for these tests
 */
abstract class BaseMultistoreTest extends BaseDirectDbEntityUpdateTest {

    protected const DEFAULT_WEBSITE_ID = 1;
    protected const DEFAULT_STORE_ID = 0;
    protected const STORE_1_ID = 1;

    private int $store2Id;
    private int $secondWebsiteId;
    private int $secondWebsiteStoreId;

    public function setUp(): void {
        parent::setUp();

        $wasDataCreated = $this->callMagentoPutEndpoint('stores/setup', []);
        if ($wasDataCreated) {
            $this->indexerOperations->flushCache();
        }

        $this->store2Id = $this->selectByCode('store_id', 'store', 'store_2_view');
        $this->secondWebsiteId = $this->selectByCode('website_id', 'store_website', 'second_website');
        $this->secondWebsiteStoreId = $this->selectByCode('store_id', 'store', 'store_for_second_website_view');
    }

    private function selectByCode(string $idField, string $table, string $code): int {
        return intval(
            $this->db->selectSingleValue("SELECT $idField FROM $table WHERE code = '$code'")
        );
    }

    protected function getStore2Id(): int {
        return $this->store2Id;
    }

    protected function getSecondWebsiteId(): int {
        return $this->secondWebsiteId;
    }

    protected function getSecondWebsiteStoreId(): int {
        return $this->secondWebsiteStoreId;
    }
}