<?php

namespace Model\Indexer\DataProvider\Category;

use PHPUnit\Framework\TestCase;
use StreamX\ConnectorCatalog\Model\Indexer\DataProvider\Category\CategoryDataFormatter;
use StreamX\ConnectorCatalog\Model\ResourceModel\Category;
use StreamX\ConnectorCatalog\Model\ResourceModel\Category\Children;
use StreamX\ConnectorCatalog\Model\SlugGenerator;
use StreamX\ConnectorCatalog\Model\SystemConfig\CatalogConfig;

class CategoryDataFormatterTest extends TestCase
{

    public function testComputeSlug() {
        // test all possible combinations
        $this->assertComputeSlug(false, false, null, 'bags-4');
        $this->assertComputeSlug(false, false, 'abc', 'bags-4');
        $this->assertComputeSlug(false, true, null, 'bags-4');
        $this->assertComputeSlug(false, true, 'abc', 'abc-4');
        $this->assertComputeSlug(true, false, null, 'bags-4');
        $this->assertComputeSlug(true, false, 'abc', 'abc');
        $this->assertComputeSlug(true, true, null, 'bags-4');
        $this->assertComputeSlug(true, true, 'abc', 'abc');
    }

    private function assertComputeSlug(bool $useUrlKey, bool $useUrlKeyAndId, ?string $urlKey, string $expectedSlug): void
    {
        // given
        $catalogConfigMock = $this->createMock(CatalogConfig::class);
        $catalogConfigMock->method('useUrlKeyToGenerateSlug')->willReturn($useUrlKey);
        $catalogConfigMock->method('useUrlKeyAndIdToGenerateSlug')->willReturn($useUrlKeyAndId);

        $slugGenerator = new SlugGenerator($catalogConfigMock);

        // and
        $category = [
            'id' => 4,
            'name' => 'Bags',
            'url_key' => $urlKey,
        ];

        // when
        $service = new CategoryDataFormatter(
            $this->createMock(Category::class),
            $this->createMock(Children::class),
            $slugGenerator
        );
        $slug = $service->computeSlug($category);

        // then
        $this->assertEquals($expectedSlug, $slug);
    }
}
