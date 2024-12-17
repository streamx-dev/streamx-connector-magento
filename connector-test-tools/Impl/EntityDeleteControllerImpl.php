<?php

namespace StreamX\ConnectorTestTools\Impl;

use Exception;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use StreamX\ConnectorTestTools\Api\EntityDeleteControllerInterface;

class EntityDeleteControllerImpl implements EntityDeleteControllerInterface {

    private ProductRepositoryInterface $productRepository;
    private CategoryRepositoryInterface $categoryRepository;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        CategoryRepositoryInterface $categoryRepository
    ) {
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
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
}