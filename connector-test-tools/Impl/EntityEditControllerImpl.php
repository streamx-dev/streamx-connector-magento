<?php

namespace StreamX\ConnectorTestTools\Impl;

use Exception;
use Magento\Catalog\Api\CategoryLinkManagementInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\ProductFactory;
use Magento\Eav\Api\AttributeRepositoryInterface;
use StreamX\ConnectorTestTools\Api\EntityEditControllerInterface;

class EntityEditControllerImpl implements EntityEditControllerInterface {

    private ProductFactory $productFactory;
    private CategoryFactory $categoryFactory;
    private ProductRepositoryInterface $productRepository;
    private CategoryRepositoryInterface $categoryRepository;
    private AttributeRepositoryInterface $attributeRepository;
    private CategoryLinkManagementInterface $categoryLinkManagement;

    public function __construct(
        ProductFactory $productFactory,
        CategoryFactory $categoryFactory,
        ProductRepositoryInterface $productRepository,
        CategoryRepositoryInterface $categoryRepository,
        AttributeRepositoryInterface $attributeRepository,
        CategoryLinkManagementInterface $categoryLinkManagement
    ) {
        $this->productFactory = $productFactory;
        $this->categoryFactory = $categoryFactory;
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

    /**
     * @inheritdoc
     */
    public function changeProductCategory(int $productId, int $oldCategoryId, int $newCategoryId): void {
        try {
            $product = $this->productRepository->getById($productId);

            /** @var string[] $oldCategoryIds */
            $oldCategoryIds = $product->getCategoryIds();
            $newCategoryIds = $this->computeNewCategoryIds($oldCategoryIds, (string)$oldCategoryId, $newCategoryId);

            $this->categoryLinkManagement->assignProductToCategories($product->getSku(), $newCategoryIds);
        } catch (Exception $e) {
            throw new Exception("Error changing product $productId category from $oldCategoryId to $newCategoryId: " . $e->getMessage(), -1, $e);
        }
    }

    private function computeNewCategoryIds(array $oldCategoryIds, string $categoryIdToRemove, string $newCategoryId): array {
        $newCategoryIds = [];
        foreach ($oldCategoryIds as $existingCategoryId) {
            if ($existingCategoryId !== $categoryIdToRemove) {
                $newCategoryIds[] = $existingCategoryId;
            }
        }
        if (!in_array($newCategoryId, $newCategoryIds)) {
            $newCategoryIds[] = $newCategoryId;
        }
        return $newCategoryIds;
    }

    /**
     * @inheritdoc
     */
    public function changeProductAttribute(int $productId, string $attributeCode, string $newValue): void {
        $productModel = $this->productFactory->create()->load($productId);
        $productModel->setData($attributeCode, $newValue);
        $productModel->getResource()->saveAttribute($productModel, $attributeCode);

        $productEntity = $this->productRepository->getById($productId);
        $this->productRepository->save($productEntity);
    }

    /**
     * @inheritdoc
     */
    public function addProductToCategory(int $categoryId, int $productId): void {
        $postedProducts = $this->loadAssignedProducts($categoryId);
        if (!array_key_exists($productId, $postedProducts)) {
            $postedProducts[$productId] = 1 + max(array_values($postedProducts));
        }
        $this->setProductsInCategory($categoryId, $postedProducts);
    }

    /**
     * @inheritdoc
     */
    public function removeProductFromCategory(int $categoryId, int $productId): void {
        $postedProducts = $this->loadAssignedProducts($categoryId);
        if (array_key_exists($productId, $postedProducts)) {
            unset($postedProducts[$productId]);
        }
        $this->setProductsInCategory($categoryId, $postedProducts);
    }

    /**
     * @return array: key = product ID, value = position in the category
     */
    private function loadAssignedProducts(int $categoryId): array {
        $assignedProducts = $this->categoryLinkManagement->getAssignedProducts($categoryId);
        $resultMap = [];
        foreach ($assignedProducts as $assignedProduct) {
            $sku = $assignedProduct->getSku();
            $productId = $this->productRepository->get($sku)->getId();
            $resultMap[$productId] = $assignedProduct->getPosition();
        }
        return $resultMap;
    }

    private function setProductsInCategory(int $categoryId, array $postedProducts): void {
        $this->categoryFactory->create()
            ->load($categoryId)
            ->setPostedProducts($postedProducts)
            ->save();
    }
}