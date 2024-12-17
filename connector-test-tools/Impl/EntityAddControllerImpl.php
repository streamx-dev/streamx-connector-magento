<?php

namespace StreamX\ConnectorTestTools\Impl;

use DateTime;
use Exception;
use Magento\Catalog\Api\CategoryLinkRepositoryInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryProductLinkInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ProductFactory;
use StreamX\ConnectorTestTools\Api\EntityAddControllerInterface;

class EntityAddControllerImpl implements EntityAddControllerInterface {

    private ProductFactory $productFactory;
    private CategoryFactory $categoryFactory;
    private ProductRepositoryInterface $productRepository;
    private CategoryRepositoryInterface $categoryRepository;
    private CategoryLinkRepositoryInterface $categoryLinkRepository;
    private CategoryProductLinkInterfaceFactory $categoryProductLinkFactory;

    public function __construct(
        ProductFactory $productFactory,
        CategoryFactory $categoryFactory,
        ProductRepositoryInterface $productRepository,
        CategoryRepositoryInterface $categoryRepository,
        CategoryLinkRepositoryInterface $categoryLinkRepository,
        CategoryProductLinkInterfaceFactory $categoryProductLinkFactory
    ) {
        $this->productFactory = $productFactory;
        $this->categoryFactory = $categoryFactory;
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
        $this->categoryLinkRepository = $categoryLinkRepository;
        $this->categoryProductLinkFactory = $categoryProductLinkFactory;
    }

    /**
     * @inheritdoc
     */
    public function addProduct(string $productName, int $categoryId): int {
        $sku = (string) (new DateTime())->getTimestamp();
        $quantity = 100;
        $price = 35;
        $defaultAttributeSetId = 4;
        $websiteId = 1;

        try {
            $product = $this->productFactory->create()
                ->setSku($sku)
                ->setName($productName)
                ->setCustomAttribute('meta_title', $productName)
                ->setCustomAttribute('meta_description', $productName)
                ->setPrice($price)
                ->setTypeId(Type::TYPE_SIMPLE)
                ->setAttributeSetId($defaultAttributeSetId)
                ->setStatus(Status::STATUS_ENABLED)
                ->setVisibility(Visibility::VISIBILITY_BOTH)
                ->setWebsiteIds([$websiteId])
                ->setStockData([
                    'qty' => $quantity,
                    'is_in_stock' => 1,
                    'manage_stock' => 1
                ]);

            $savedProduct = $this->productRepository->save($product);
            $productId = $savedProduct->getId();
            $this->addProductToCategory($sku, $categoryId);

            return $productId;
        } catch (Exception $e) {
            throw new Exception("Error adding product $productName: " . $e->getMessage(), -1, $e);
        }
    }

    /**
     * @throws Exception
     */
    private function addProductToCategory(string $sku, int $categoryId): void {
        $categoryProductLink = $this->categoryProductLinkFactory->create();
        $categoryProductLink->setCategoryId($categoryId);
        $categoryProductLink->setSku($sku);

        $this->categoryLinkRepository->save($categoryProductLink);
    }

    /**
     * @inheritdoc
     */
    public function addCategory(string $categoryName): int {
        $parentCategoryId = 2;

        try {
            $category = $this->categoryFactory->create();
            $category->setName($categoryName);

            $category->setName($categoryName)
                ->setParentId($parentCategoryId)
                ->setIsActive(true);

            $savedCategory = $this->categoryRepository->save($category);
            $categoryId = $savedCategory->getId();
            $this->setCategoryPath($categoryId, $parentCategoryId);
            return $categoryId;
        } catch (Exception $e) {
            throw new Exception("Error adding category $categoryName: " . $e->getMessage(), -1, $e);
        }
    }

    /**
     * @throws Exception
     */
    private function setCategoryPath(int $categoryId, int $parentCategoryId): void {
        $category = $this->categoryRepository->get($categoryId);
        $category->setPath("$parentCategoryId/$categoryId");
        $this->categoryRepository->save($category);
    }
}