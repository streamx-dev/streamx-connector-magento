<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product;

/**
 * When product description is edited using Page Builder on the Magento Admin UI, it wraps (and encodes) the description in an additional html tag.
 * This class provides way to unwrap the description
 */
class ProductDescriptionUnwrapper {

    private const WRAPPED_DESCRIPTION_PATTERN = '|^<div data-content-type="html".*?>(.*)</div>$|s';

    public static function unwrapIfWrapped(string $description): string {
        $matches = [];
        preg_match(self::WRAPPED_DESCRIPTION_PATTERN, $description, $matches);

        if (count($matches) == 2) { // match[0] is the whole string, match[1] is the div's content
            return html_entity_decode($matches[1]);
        }

        return $description;
    }
}