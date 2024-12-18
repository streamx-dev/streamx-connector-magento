<?php

namespace StreamX\ConnectorTestTools\Impl;

use Exception;
use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product;
use Magento\Eav\Api\AttributeRepositoryInterface;
use StreamX\ConnectorTestTools\Api\EntityEditControllerInterface;

class EntityEditControllerImpl  implements EntityEditControllerInterface {

    private ProductRepositoryInterface $productRepository;
    private CategoryRepositoryInterface $categoryRepository;
    private AttributeRepositoryInterface $attributeRepository;
    private CategoryLinkManagementInterface $categoryLinkManagement;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        CategoryRepositoryInterface $categoryRepository,
        AttributeRepositoryInterface $attributeRepository,
        CategoryLinkManagementInterface $categoryLinkManagement,
    ) {
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
        $this->attributeRepository = $attributeRepository;
        $this->categoryLinkManagement = $categoryLinkManagement;
    }

    /**
     * @inheritdoc
     */
    public function renameProduct(int $productId, string $newName): void {
        try {
            $product = $this->productRepository->getById($productId);
            $product->setName($newName);
            $this->productRepository->save($product);
        } catch (Exception $e) {
            throw new Exception("Error renaming product with ID $productId: " . $e->getMessage(), -1, $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function renameCategory(int $categoryId, string $newName): void {
        try {
            $category = $this->categoryRepository->get($categoryId);
            $category->setName($newName);
            $this->categoryRepository->save($category);
        } catch (Exception $e) {
            throw new Exception("Error renaming category with ID $categoryId: " . $e->getMessage(), -1, $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function renameAttribute(string $attributeCode, string $newName): void {
        try {
            $attribute = $this->attributeRepository->get(Product::ENTITY, $attributeCode);
            $attribute->setDefaultFrontendLabel($newName);
            $this->attributeRepository->save($attribute);
        } catch (Exception $e) {
            throw new Exception("Error renaming product attribute '$attributeCode': " . $e->getMessage(), -1, $e);
        }
    }

    public function changeProductCategory(int $productId, int $oldCategoryId, int $newCategoryId): void {
        try {
            $product = $this->productRepository->getById($productId);
            $sku = $product->getSku();

            $categoryIds = [$newCategoryId];
            foreach ($product->getCategoryIds() as $existingCategoryId) {
                if ((int) $existingCategoryId !== $oldCategoryId) {
                    $categoryIds[] = $existingCategoryId;
                }
            }

            $this->categoryLinkManagement->assignProductToCategories($sku, $categoryIds);
        } catch (Exception $e) {
            throw new Exception("Error changing product $productId category from $oldCategoryId to $newCategoryId: " . $e->getMessage(), -1, $e);
        }
    }
}