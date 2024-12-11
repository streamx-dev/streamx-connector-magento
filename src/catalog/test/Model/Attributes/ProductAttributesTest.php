<?php

namespace StreamX\ConnectorCatalog\Test\Model\Attributes;

use PHPUnit\Framework\TestCase;
use StreamX\ConnectorCatalog\Model\Attributes\ProductAttributes;
use StreamX\ConnectorCatalog\Api\CatalogConfigurationInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class ProductAttributesTest extends TestCase
{
    private ObjectManager $objectManager;
    private CatalogConfigurationInterface $catalogConfigMock;
    private ProductAttributes $productAttributes;

    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
        $this->catalogConfigMock = $this->createMock(CatalogConfigurationInterface::class);
        $this->productAttributes = $this->objectManager->getObject(
            ProductAttributes::class,
            ['catalogConfiguration' => $this->catalogConfigMock]
        );
    }

    /**
     * @dataProvider provideAllowedAttributes
     */
    public function testGetAttributes(int $storeId, array $selectedAttributes)
    {
        $attributes = ProductAttributes::REQUIRED_ATTRIBUTES;
        $this->catalogConfigMock->expects($this->once())
            ->method('getAllowedAttributesToIndex')
            ->with($storeId)
            ->willReturn($selectedAttributes);

        $productAttributes = $this->productAttributes->getAttributes($storeId);

        foreach ($attributes as $attributeCode) {
            $this->assertContains($attributeCode, $productAttributes);
        }
    }

    public function testGetAllAttributes()
    {
        $storeId = 2;

        $this->catalogConfigMock->expects($this->once())
            ->method('getAllowedAttributesToIndex')
            ->with($storeId)
            ->willReturn([]);

        $productAttributes = $this->productAttributes->getAttributes($storeId);
        $this->assertEmpty($productAttributes);
    }

    public function provideAllowedAttributes(): array
    {
        return [
            [
                'storeId' => 1,
                'attributes' => [
                    'sku',
                    'url_path',
                    'url_key',
                    'name',
                    'price',
                    'visibility',
                    'status',
                    'price_type',
                ]
            ]
        ];
    }
}
