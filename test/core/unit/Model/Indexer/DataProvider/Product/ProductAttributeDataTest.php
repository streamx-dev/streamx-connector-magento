<?php

namespace StreamX\ConnectorCore\test\unit\Model\Indexer\DataProvider\Product;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use StreamX\ConnectorCatalog\Model\Attributes\ProductAttributes;
use StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Product\ProductAttributeData;
use StreamX\ConnectorCatalog\Model\ResourceModel\Product\LoadAttributeDefinitions;
use StreamX\ConnectorCatalog\Model\ResourceModel\Product\ProductAttributesProvider;
use StreamX\ConnectorCatalog\Model\SlugGenerator;
use StreamX\ConnectorCatalog\Model\SystemConfig\CatalogConfig;
use StreamX\ConnectorCore\Indexer\ImageUrlManager;

class ProductAttributeDataTest extends TestCase
{
    /** @test */
    public function shouldApplySlug() {
        // test all possible combinations
        $this->assertApplySlug(false, false, null, 'joust-duffle-bag-1');
        $this->assertApplySlug(false, false, 'abc', 'joust-duffle-bag-1');
        $this->assertApplySlug(false, true, null, 'joust-duffle-bag-1');
        $this->assertApplySlug(false, true, 'abc', 'abc-1');
        $this->assertApplySlug(true, false, null, 'joust-duffle-bag-1');
        $this->assertApplySlug(true, false, 'abc', 'abc');
        $this->assertApplySlug(true, true, null, 'joust-duffle-bag-1');
        $this->assertApplySlug(true, true, 'abc', 'abc');
    }

    private function assertApplySlug(bool $useUrlKey, bool $useUrlKeyAndId, ?string $urlKey, string $expectedSlug): void
    {
        // given
        $catalogConfigMock = $this->createMock(CatalogConfig::class);
        $catalogConfigMock->method('useUrlKeyToGenerateSlug')->willReturn($useUrlKey);
        $catalogConfigMock->method('useUrlKeyAndIdToGenerateSlug')->willReturn($useUrlKeyAndId);

        $slugGenerator = new SlugGenerator($catalogConfigMock);

        // and
        $product = [
            'id' => 1,
            'name' => 'Joust Duffle Bag',
            'url_key' => $urlKey,
        ];

        // when
        $service = new ProductAttributeData(
            $this->createMock(LoggerInterface::class),
            $this->createMock(ProductAttributes::class),
            $this->createMock(LoadAttributeDefinitions::class),
            $this->createMock(ProductAttributesProvider::class),
            $this->createMock(ImageUrlManager::class),
            $slugGenerator
        );
        $service->applySlug($product);
        $slug = $product['slug'];

        // then
        $this->assertEquals($expectedSlug, $slug);
    }
}
