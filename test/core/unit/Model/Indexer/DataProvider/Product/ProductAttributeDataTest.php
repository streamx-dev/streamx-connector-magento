<?php

namespace StreamX\ConnectorCore\test\unit\Model\Indexer\DataProvider\Product;

use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use StreamX\ConnectorCatalog\Model\Attributes\ProductAttributes;
use StreamX\ConnectorCatalog\Model\Config\Source\SlugOptionsSource;
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
        $this->assertApplySlug(SlugOptionsSource::NAME_AND_ID, null, 'joust-duffle-bag-1');
        $this->assertApplySlug(SlugOptionsSource::NAME_AND_ID, 'abc', 'joust-duffle-bag-1');
        $this->assertApplySlug(SlugOptionsSource::URL_KEY_AND_ID, null, 'joust-duffle-bag-1');
        $this->assertApplySlug(SlugOptionsSource::URL_KEY_AND_ID, 'abc', 'abc-1');
        $this->assertApplySlug(SlugOptionsSource::URL_KEY, null, 'joust-duffle-bag-1');
        $this->assertApplySlug(SlugOptionsSource::URL_KEY, 'abc', 'abc');
    }

    private function assertApplySlug(int $slugGenerationStrategy, ?string $urlKey, string $expectedSlug): void
    {
        // given
        $catalogConfigMock = $this->createMock(CatalogConfig::class);
        $catalogConfigMock->method('slugGenerationStrategy')->willReturn($slugGenerationStrategy);

        $slugGenerator = new SlugGenerator($catalogConfigMock);

        // and
        $product = [
            'id' => 1,
            'name' => 'Joust Duffle Bag',
            'attributes' => [
                [
                    'name' => 'price',
                    'values' => [
                        [
                            'label' => "123 label",
                            'value' => 123
                        ]
                    ]
                ],
                [
                    'name' => 'url_key',
                    'values' => [
                        [
                            'label' => "$urlKey label",
                            'value' => $urlKey
                        ]
                    ]
                ]
            ]
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
