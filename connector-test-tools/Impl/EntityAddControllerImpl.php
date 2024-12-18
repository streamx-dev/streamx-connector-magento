<?php

namespace StreamX\ConnectorTestTools\Impl;

use DateTime;
use Exception;
use Magento\Catalog\Api\CategoryLinkRepositoryInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryProductLinkInterfaceFactory;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use StreamX\ConnectorTestTools\Api\EntityAddControllerInterface;

class EntityAddControllerImpl implements EntityAddControllerInterface {

    private ProductFactory $productFactory;
    private CategoryFactory $categoryFactory;
    private EavSetupFactory $eavSetupFactory;
    private AttributeFactory $attributeFactory;
    private ModuleDataSetupInterface $moduleDataSetup;
    private ProductRepositoryInterface $productRepository;
    private CategoryRepositoryInterface $categoryRepository;
    private CategoryLinkRepositoryInterface $categoryLinkRepository;
    private CategoryProductLinkInterfaceFactory $categoryProductLinkFactory;

    public function __construct(
        ProductFactory $productFactory,
        CategoryFactory $categoryFactory,
        EavSetupFactory $eavSetupFactory,
        AttributeFactory $attributeFactory,
        ModuleDataSetupInterface $moduleDataSetup,
        ProductRepositoryInterface $productRepository,
        CategoryRepositoryInterface $categoryRepository,
        CategoryLinkRepositoryInterface $categoryLinkRepository,
        CategoryProductLinkInterfaceFactory $categoryProductLinkFactory
    ) {
        $this->productFactory = $productFactory;
        $this->categoryFactory = $categoryFactory;
        $this->eavSetupFactory = $eavSetupFactory;
        $this->attributeFactory = $attributeFactory;
        $this->moduleDataSetup = $moduleDataSetup;
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
            $category = $this->categoryFactory->create()
                ->setName($categoryName)
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

    /**
     * @inheritdoc
     */
    public function addAttribute(string $attributeCode): int {
        $displayName = "The $attributeCode attribute";
        try {
            $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
            $entityTypeId = $eavSetup->getEntityTypeId(Product::ENTITY);

            $attribute = $this->attributeFactory->create()
                ->setAttributeCode($attributeCode)
                ->setEntityTypeId($entityTypeId)
                ->setBackendType('text')
                ->setFrontendInput('textarea')
                ->setDefaultFrontendLabel($displayName)
                ->setIsUserDefined(true)
                ->setIsVisible(true)
                ->setIsVisibleOnFront(true)
                ->setUsedInProductListing(true);

            return $attribute->save()->getAttributeId();
        } catch (Exception $e) {
            throw new Exception("Error adding attribute $attributeCode: " . $e->getMessage(), -1, $e);
        }
    }
}