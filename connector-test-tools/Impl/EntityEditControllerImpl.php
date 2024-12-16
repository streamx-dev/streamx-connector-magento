<?php

namespace StreamX\ConnectorTestTools\Impl;

use Exception;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use StreamX\ConnectorTestTools\Api\EntityEditControllerInterface;

class EntityEditControllerImpl  implements EntityEditControllerInterface {

    private ProductRepositoryInterface $productRepository;
    private CategoryRepositoryInterface $categoryRepository;
    private AttributeRepositoryInterface $attributeRepository;

    public function __construct(
        ProductRepositoryInterface $productRepository,
        CategoryRepositoryInterface $categoryRepository,
        AttributeRepositoryInterface $attributeRepository
    ) {
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
        $this->attributeRepository = $attributeRepository;
    }

    /**
     * @inheritdoc
     */
    public function renameProduct(int $productId, string $newName) {
        try {
            $product = $this->productRepository->getById($productId);
            $product->setName($newName);
            $this->productRepository->save($product);
        } catch (NoSuchEntityException $e) {
            throw new Exception("Product with ID $productId does not exist: " . $e->getMessage(), -1, $e);
        } catch (Exception $e) {
            throw new Exception("Error renaming product with ID $productId: " . $e->getMessage(), -2, $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function renameCategory(int $categoryId, string $newName) {
        try {
            $category = $this->categoryRepository->get($categoryId);
            $category->setName($newName);
            $this->categoryRepository->save($category);
        } catch (NoSuchEntityException $e) {
            throw new Exception("Category with ID $categoryId does not exist: " . $e->getMessage(), -1, $e);
        } catch (Exception $e) {
            throw new Exception("Error renaming category with ID $categoryId: " . $e->getMessage(), -2, $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function renameAttribute(string $attributeCode, string $newName) {
        try {
            $attribute = $this->attributeRepository->get('catalog_product', $attributeCode);
            $attribute->setDefaultFrontendLabel($newName);
            $this->attributeRepository->save($attribute);
        } catch (NoSuchEntityException $e) {
            throw new Exception("Product attribute '$attributeCode' does not exist: " . $e->getMessage(), -1, $e);
        } catch (Exception $e) {
            throw new Exception("Error renaming product attribute '$attributeCode': " . $e->getMessage(), -2, $e);
        }
    }
}