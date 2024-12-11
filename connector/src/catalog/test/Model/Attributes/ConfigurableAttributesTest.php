<?php

namespace StreamX\ConnectorCatalog\Test\Model\Attributes;

use PHPUnit\Framework\TestCase;
use StreamX\ConnectorCatalog\Model\Attributes\ConfigurableAttributes;
use StreamX\ConnectorCatalog\Api\CatalogConfigurationInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class ConfigurableAttributesTest extends TestCase
{
    private ObjectManager $objectManager;
    private CatalogConfigurationInterface $catalogConfigMock;
    private ConfigurableAttributes $configurableAttributes;

    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
        $this->catalogConfigMock = $this->createMock(CatalogConfigurationInterface::class);
        $this->configurableAttributes = $this->objectManager->getObject(
            ConfigurableAttributes::class,
            ['catalogConfiguration' => $this->catalogConfigMock]
        );
    }

    /**
     * @dataProvider provideAllowedAttributes
     */
    public function testGetChildrenRequiredAttributes(int $storeId, array $selectedAttributes)
    {
        $attributes = ConfigurableAttributes::MINIMAL_ATTRIBUTE_SET;

        $this->catalogConfigMock->expects($this->once())
            ->method('getAllowedChildAttributesToIndex')
            ->with($storeId)
            ->willReturn($selectedAttributes);

        $productAttributes = $this->configurableAttributes->getChildrenRequiredAttributes($storeId);

        foreach ($attributes as $attributeCode) {
            $this->assertContains($attributeCode, $productAttributes);
        }
    }

    public function testGetAllAttributes()
    {
        $storeId = 1;

        $this->catalogConfigMock->expects($this->once())
            ->method('getAllowedChildAttributesToIndex')
            ->willReturn([]);

        $productAttributes = $this->configurableAttributes->getChildrenRequiredAttributes($storeId);
        $this->assertEmpty($productAttributes);
    }

    public function provideAllowedAttributes(): array
    {
        return [
            [
                'storeId' => 1,
                'attributes' => [
                    'sku',
                    'status',
                    'visibility',
                    'name',
                    'price',
                ]
            ],
            [
                'storeId' => 1,
                'attributes' => [
                    'tax_class_id',
                ]
            ]
        ];
    }
}
