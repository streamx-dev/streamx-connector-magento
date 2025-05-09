<?php

namespace StreamX\ConnectorCore\test\unit\Model\Indexer\DataProvider\Category;

use PHPUnit\Framework\TestCase;
use StreamX\ConnectorCatalog\Model\Config\Source\SlugOptionsSource;
use StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Category\CategoryDataFormatter;
use StreamX\ConnectorCatalog\Model\ResourceModel\Category\Children;
use StreamX\ConnectorCatalog\Model\SlugGenerator;
use StreamX\ConnectorCatalog\Model\SystemConfig\CatalogConfig;

class CategoryDataFormatterTest extends TestCase
{
    /** @test */
    public function shouldComputeSlug() {
        // test all possible combinations
        $this->assertComputeSlug(SlugOptionsSource::NAME_AND_ID, null, 'bags-4');
        $this->assertComputeSlug(SlugOptionsSource::NAME_AND_ID, 'abc', 'bags-4');
        $this->assertComputeSlug(SlugOptionsSource::URL_KEY_AND_ID, null, 'bags-4');
        $this->assertComputeSlug(SlugOptionsSource::URL_KEY_AND_ID, 'abc', 'abc-4');
        $this->assertComputeSlug(SlugOptionsSource::URL_KEY, null, 'bags-4');
        $this->assertComputeSlug(SlugOptionsSource::URL_KEY, 'abc', 'abc');
    }

    private function assertComputeSlug(int $slugGenerationStrategy, ?string $urlKey, string $expectedSlug): void
    {
        // given
        $catalogConfigMock = $this->createMock(CatalogConfig::class);
        $catalogConfigMock->method('slugGenerationStrategy')->willReturn($slugGenerationStrategy);

        $slugGenerator = new SlugGenerator($catalogConfigMock);

        // and
        $category = [
            'id' => 4,
            'name' => 'Bags',
            'url_key' => $urlKey,
        ];

        // when
        $service = new CategoryDataFormatter(
            $this->createMock(Children::class),
            $slugGenerator
        );
        $slug = $service->computeSlug($category);

        // then
        $this->assertEquals($expectedSlug, $slug);
    }
}
