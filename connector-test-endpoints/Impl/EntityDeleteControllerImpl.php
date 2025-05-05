<?php

namespace StreamX\ConnectorTestEndpoints\Impl;

use Exception;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use StreamX\ConnectorTestEndpoints\Api\EntityDeleteControllerInterface;

class EntityDeleteControllerImpl implements EntityDeleteControllerInterface {

    private EavSetupFactory $eavSetupFactory;
    private ModuleDataSetupInterface $moduleDataSetup;
    private ProductRepositoryInterface $productRepository;
    private CategoryRepositoryInterface $categoryRepository;
    private AttributeRepositoryInterface $attributeRepository;

    public function __construct(
        EavSetupFactory $eavSetupFactory,
        ModuleDataSetupInterface $moduleDataSetup,
        ProductRepositoryInterface $productRepository,
        CategoryRepositoryInterface $categoryRepository,
        AttributeRepositoryInterface $attributeRepository
    ) {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->moduleDataSetup = $moduleDataSetup;
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
        $this->attributeRepository = $attributeRepository;
    }

    /**
     * @inheritdoc
     */
    public function deleteProduct(int $productId): void {
        try {
            $sku = $this->productRepository->getById($productId)->getSku();
            $this->productRepository->deleteById($sku);
        } catch (Exception $e) {
            throw new Exception("Error deleting product $productId: " . $e->getMessage(), -1, $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function deleteCategory(int $categoryId): void {
        try {
            $this->categoryRepository->deleteByIdentifier($categoryId);
        } catch (Exception $e) {
            throw new Exception("Error deleting category $categoryId: " . $e->getMessage(), -1, $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function deleteAttribute(int $attributeId): void {
        try {
            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
            $attributeCode = $eavSetup->getAttribute(Product::ENTITY, $attributeId)['attribute_code'];
            $attribute = $this->attributeRepository->get(Product::ENTITY, $attributeCode);
            $attribute->delete();
        } catch (Exception $e) {
            throw new Exception("Error deleting attribute $attributeId: " . $e->getMessage(), -1, $e);
        }
    }
}