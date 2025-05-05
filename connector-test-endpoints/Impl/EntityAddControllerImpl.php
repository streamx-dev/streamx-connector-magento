<?php

namespace StreamX\ConnectorTestEndpoints\Impl;

use DateTime;
use Exception;
use InvalidArgumentException;
use Magento\Catalog\Api\CategoryLinkRepositoryInterface;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryProductLinkInterfaceFactory;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Catalog\Model\Product;
use Magento\Catalog\Model\Product\Attribute\Source\Status;
use Magento\Catalog\Model\Product\Type;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory;
use Magento\Eav\Model\Config;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Eav\Api\AttributeManagementInterface;
use Magento\Framework\DB\Transaction;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use StreamX\ConnectorTestEndpoints\Api\EntityAddControllerInterface;

class EntityAddControllerImpl implements EntityAddControllerInterface {

    private Config $eavConfig;
    private ProductFactory $productFactory;
    private CategoryFactory $categoryFactory;
    private EavSetupFactory $eavSetupFactory;
    private AttributeFactory $attributeFactory;
    private ModuleDataSetupInterface $moduleDataSetup;
    private ProductRepositoryInterface $productRepository;
    private CategoryRepositoryInterface $categoryRepository;
    private AttributeManagementInterface $attributeManagement;
    private CategoryLinkRepositoryInterface $categoryLinkRepository;
    private CategoryProductLinkInterfaceFactory $categoryProductLinkFactory;

    public function __construct(
        Config $eavConfig,
        ProductFactory $productFactory,
        CategoryFactory $categoryFactory,
        EavSetupFactory $eavSetupFactory,
        AttributeFactory $attributeFactory,
        ModuleDataSetupInterface $moduleDataSetup,
        ProductRepositoryInterface $productRepository,
        CategoryRepositoryInterface $categoryRepository,
        AttributeManagementInterface $attributeManagement,
        CategoryLinkRepositoryInterface $categoryLinkRepository,
        CategoryProductLinkInterfaceFactory $categoryProductLinkFactory
    ) {
        $this->eavConfig = $eavConfig;
        $this->productFactory = $productFactory;
        $this->categoryFactory = $categoryFactory;
        $this->eavSetupFactory = $eavSetupFactory;
        $this->attributeFactory = $attributeFactory;
        $this->moduleDataSetup = $moduleDataSetup;
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
        $this->attributeManagement = $attributeManagement;
        $this->categoryLinkRepository = $categoryLinkRepository;
        $this->categoryProductLinkFactory = $categoryProductLinkFactory;
    }

    /**
     * @inheritdoc
     */
    public function addProduct(string $productName, array $categoryIds): int {
        $sku = (string) (new DateTime())->getTimestamp();
        $quantity = 100;
        $price = 350;
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

            $transaction = new Transaction();
            $transaction->addObject($product);
            $transaction->addCommitCallback(function () use ($sku, $categoryIds, $product) {
                foreach ($categoryIds as $categoryId) {
                    $this->addProductToCategory($sku, $categoryId);
                }
                $this->addAttributeOptionsToProduct($product, 'color', ['Brown']);
                $this->addAttributeOptionsToProduct($product, 'material', ['Metal', 'Plastic', 'Leather']);
            });
            $transaction->save();

            return $this->productRepository->get($sku)->getId();
        } catch (Exception $e) {
            throw new Exception("Error adding product $productName: " . $e->getMessage(), -1, $e);
        }
    }

    private function addAttributeOptionsToProduct(ProductInterface $product, string $attributeCode, array $displayLabels): void {
        $optionIds = [];
        foreach ($displayLabels as $displayLabel) {
            $optionIds[] = $this->getAttributeOptionValue($attributeCode, $displayLabel);
        }
        $this->addAttributeToProduct($product, $attributeCode, implode(',', $optionIds));
    }

    private function addAttributeToProduct(ProductInterface $product, string $attributeCode, $attributeValue): void {
        $product->setData($attributeCode, $attributeValue);
        $product->getResource()->saveAttribute($product, $attributeCode);
    }

    private function getAttributeOptionValue(string $attributeCode, string $displayLabel) {
        $attribute = $this->eavConfig->getAttribute('catalog_product', $attributeCode);
        $options = $attribute->getSource()->getAllOptions();

        foreach ($options as $option) {
            if ($option['label'] === $displayLabel) {
                return $option['value'];
            }
        }
        throw new InvalidArgumentException("No option for attribute $attributeCode with value $displayLabel");
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
    public function addCategory(string $categoryName, int $parentCategoryId): int {
        try {
            $category = $this->categoryFactory->create()
                ->setName($categoryName)
                ->setParentId($parentCategoryId)
                ->setIsActive(true);

            return $this->categoryRepository->save($category)->getId();
        } catch (Exception $e) {
            throw new Exception("Error adding category $categoryName: " . $e->getMessage(), -1, $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function addAttributeAndAssignToProduct(string $attributeCode, int $productId): int {
        try {
            $attribute = $this->addNewTextAttribute($attributeCode);
            $this->addAttributeToDefaultAttributeSet($attribute);

            $product = $this->productFactory->create()->load($productId);
            $attributeValue = "$attributeCode value for product " . $product->getId();
            $this->addAttributeToProduct($product, $attributeCode, $attributeValue);
            $product->save();

            return $attribute->getAttributeId();
        } catch (Exception $e) {
            throw new Exception("Error adding attribute $attributeCode: " . $e->getMessage(), -1, $e);
        }
    }

    private function addNewTextAttribute(string $attributeCode): Attribute {
        $displayName = implode(' ', array_map('ucfirst', explode('_', $attributeCode))); // split by underscore, capitalize words and join with space
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $entityTypeId = $eavSetup->getEntityTypeId(Product::ENTITY);

        return $this->attributeFactory->create()
            ->setAttributeCode($attributeCode)
            ->setEntityTypeId($entityTypeId)
            ->setBackendType('text')
            ->setFrontendInput('textarea')
            ->setDefaultFrontendLabel($displayName)
            ->setIsUserDefined(true)
            ->setIsVisible(true)
            ->setIsVisibleOnFront(true)
            ->setIsFilterable(false)
            ->setUsedInProductListing(true)
            ->save();
    }

    private function addAttributeToDefaultAttributeSet(Attribute $attribute): void {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $attributeSetId = $eavSetup->getAttributeSetId(Product::ENTITY, 'default');
        $attributeGroupId = $eavSetup->getAttributeGroupId(Product::ENTITY, $attributeSetId, 'general');
        $this->attributeManagement->assign(
            Product::ENTITY,
            $attributeSetId,
            $attributeGroupId,
            $attribute->getAttributeCode(),
            999 // Sort order
        );
    }
}