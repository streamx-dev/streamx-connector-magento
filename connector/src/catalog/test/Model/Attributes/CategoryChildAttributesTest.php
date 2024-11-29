<?php

namespace StreamX\ConnectorCatalog\Test\Model\Attributes;

use StreamX\ConnectorCatalog\Model\Attributes\CategoryAttributes;
use StreamX\ConnectorCatalog\Model\Attributes\CategoryChildAttributes;
use StreamX\ConnectorCatalog\Model\SystemConfig\CategoryConfigInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;

class CategoryChildAttributesTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var
     */
    private $objectManager;

    /**
     * @var CategoryConfigInterface
     */
    private $catalogConfigMock;

    /**
     * @var CategoryChildAttributes
     */
    private $categoryChildAttributes;

    /**
     *
     */
    protected function setUp(): void
    {
        $this->objectManager = new ObjectManager($this);
        $this->catalogConfigMock = $this->createMock(CategoryConfigInterface::class);
        $this->categoryChildAttributes = $this->objectManager->getObject(
            CategoryChildAttributes::class,
            ['categoryConfig' => $this->catalogConfigMock]
        );
    }

    /**
     * @dataProvider provideAllowedAttributes
     */
    public function testGetChildrenRequiredAttributes(int $storeId, array $selectedAttributes)
    {
        $attributes = CategoryAttributes::MINIMAL_ATTRIBUTE_SET;

        $this->catalogConfigMock->expects($this->once())
            ->method('getAllowedChildAttributesToIndex')
            ->with($storeId)
            ->willReturn($selectedAttributes);

        $productAttributes = $this->categoryChildAttributes->getRequiredAttributes($storeId);

        foreach ($attributes as $attributeCode) {
            $this->assertContains($attributeCode, $productAttributes);
        }
    }

    /**
     *
     */
    public function testGetAllAttributes()
    {
        $storeId = 1;

        $this->catalogConfigMock->expects($this->once())
            ->method('getAllowedChildAttributesToIndex')
            ->willReturn([]);

        $productAttributes = $this->categoryChildAttributes->getRequiredAttributes($storeId);
        $this->assertEmpty($productAttributes);
    }

    public function provideAllowedAttributes(): array
    {
        return [
            [
                'storeId' => 1,
                'attributes' => [
                    'name',
                    'is_active',
                    'url_path',
                    'url_key',
                ]
            ],
            [
                'storeId' => 1,
                'attributes' => [
                    'image',
                ]
            ]
        ];
    }
}
