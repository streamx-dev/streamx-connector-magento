<?php

namespace StreamX\ConnectorCatalog\Model\SystemConfig;

/**
 * @api
 */
interface CategoryConfigInterface
{
    /**
     * @const string
     */
    const CATEGORY_SETTINGS_XML_PREFIX = 'streamx_connector_settings/catalog_category_settings';

    /**
     * Category attributes to reindex
     */
    const CATEGORY_ATTRIBUTES = 'category_attributes';
    const CHILD_ATTRIBUTES = 'child_attributes';

    /**
     * Retrieve Category Url Suffix
     */
    public function getCategoryUrlSuffix(int $storeId): string;

    /**
     * Retrieve attributes used for sort by
     *
     * @throws \Exception
     */
    public function getAttributesUsedForSortBy(): array;

    /**
     * Retrieve default product attribute used for sort by
     */
    public function getProductListDefaultSortBy(int $storeId): string;

    /**
     * Retrieve Category Attributes Allowed to export
     */
    public function getAllowedAttributesToIndex(int $storeId): array;

    /**
     * Retrieve allowed attributes for children categories
     */
    public function getAllowedChildAttributesToIndex(int $storeId): array;
}
