<?php

namespace StreamX\ConnectorTestTools\Impl;

use Exception;
use Magento\Catalog\Api\ProductRepositoryInterface;
use StreamX\ConnectorTestTools\Api\EntityDeleteControllerInterface;

class EntityDeleteControllerImpl implements EntityDeleteControllerInterface {

    private ProductRepositoryInterface $productRepository;

    public function __construct(ProductRepositoryInterface $productRepository) {
        $this->productRepository = $productRepository;
    }

    /**
     * @inheritdoc
     */
    public function deleteProduct(int $productId): void {
        try {
            $sku = $this->productRepository->getById($productId)->getSku();
            $this->productRepository->deleteById($sku);
        } catch (Exception $e) {
            throw new Exception("Error deleting product $productId" . $e->getMessage(), -1, $e);
        }
    }
}