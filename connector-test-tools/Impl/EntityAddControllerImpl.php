<?php

namespace StreamX\ConnectorTestTools\Impl;

use DateTime;
use Exception;
use Magento\Catalog\Api\CategoryLinkRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryProductLinkInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ProductFactory;
use Magento\Store\Model\StoreManager;
use StreamX\ConnectorTestTools\Api\EntityAddControllerInterface;

class EntityAddControllerImpl implements EntityAddControllerInterface {

    private StoreManager $storeManager;
    private ProductFactory $productFactory;
    private ProductRepositoryInterface $productRepository;
    private CategoryLinkRepositoryInterface $categoryLinkRepository;
    private CategoryProductLinkInterfaceFactory $categoryProductLinkFactory;

    public function __construct(
        StoreManager $storeManager,
        ProductFactory $productFactory,
        ProductRepositoryInterface $productRepository,
        CategoryLinkRepositoryInterface $categoryLinkRepository,
        CategoryProductLinkInterfaceFactory $categoryProductLinkFactory
    ) {
        $this->storeManager = $storeManager;
        $this->productFactory = $productFactory;
        $this->productRepository = $productRepository;
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
            $product = $this->productFactory->create();
            $product->setSku($sku);
            $product->setName($productName);
            $product->setCustomAttribute('meta_title', $productName);
            $product->setCustomAttribute('meta_description', $productName);
            $product->setPrice($price);
            $product->setTypeId(Type::TYPE_SIMPLE);
            $product->setAttributeSetId($defaultAttributeSetId);
            $product->setStatus(Status::STATUS_ENABLED);
            $product->setVisibility(Visibility::VISIBILITY_BOTH);
            $product->setWebsiteIds([$websiteId]);
            $product->setStockData([
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
}