<?php

namespace StreamX\ConnectorTestTools\Impl;

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
use Magento\Catalog\Model\ResourceModel\Category\CollectionFactory;
use Magento\Catalog\Model\ResourceModel\Eav\Attribute;
use Magento\Catalog\Model\ResourceModel\Eav\AttributeFactory;
use Magento\Eav\Api\AttributeRepositoryInterface;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Eav\Api\AttributeManagementInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Transaction;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use StreamX\ConnectorTestTools\Api\EntityAddControllerInterface;

class EntityAddControllerImpl implements EntityAddControllerInterface {

    private Config $eavConfig;
    private ProductFactory $productFactory;
    private CategoryFactory $categoryFactory;
    private EavSetupFactory $eavSetupFactory;
    private AttributeFactory $attributeFactory;
    private CollectionFactory $categoryCollectionFactory;
    private ResourceConnection $resourceConnection;
    private ModuleDataSetupInterface $moduleDataSetup;
    private ProductRepositoryInterface $productRepository;
    private CategoryRepositoryInterface $categoryRepository;
    private AttributeManagementInterface $attributeManagement;
    private AttributeRepositoryInterface $attributeRepository;
    private CategoryLinkRepositoryInterface $categoryLinkRepository;
    private CategoryProductLinkInterfaceFactory $categoryProductLinkFactory;

    public function __construct(
        Config $eavConfig,
        ProductFactory $productFactory,
        CategoryFactory $categoryFactory,
        EavSetupFactory $eavSetupFactory,
        AttributeFactory $attributeFactory,
        CollectionFactory $categoryCollectionFactory,
        ResourceConnection $resourceConnection,
        ModuleDataSetupInterface $moduleDataSetup,
        ProductRepositoryInterface $productRepository,
        CategoryRepositoryInterface $categoryRepository,
        AttributeManagementInterface $attributeManagement,
        AttributeRepositoryInterface $attributeRepository,
        CategoryLinkRepositoryInterface $categoryLinkRepository,
        CategoryProductLinkInterfaceFactory $categoryProductLinkFactory
    ) {
        $this->eavConfig = $eavConfig;
        $this->productFactory = $productFactory;
        $this->categoryFactory = $categoryFactory;
        $this->eavSetupFactory = $eavSetupFactory;
        $this->attributeFactory = $attributeFactory;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->resourceConnection = $resourceConnection;
        $this->moduleDataSetup = $moduleDataSetup;
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
        $this->attributeManagement = $attributeManagement;
        $this->attributeRepository = $attributeRepository;
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
    public function addCategory(string $categoryName): int {
        $parentCategoryId = 2;

        // check if the category already exists
        $categoryCollection = $this->categoryCollectionFactory->create()
            ->addAttributeToFilter('name', $categoryName)
            ->addAttributeToFilter('parent_id', $parentCategoryId);

        if ($categoryCollection->getSize() > 0) {
            return $categoryCollection->getFirstItem()->getId();
        }

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
    public function addTextAttribute(string $attributeCode): int {
        try {
            $id = $this->prepareNewTextAttribute($attributeCode)->save()->getId();
            $this->addAttributeToDefaultAttributeSet($attributeCode);
            return $id;
        } catch (Exception $e) {
            throw new Exception("Error adding attribute $attributeCode: " . $e->getMessage(), -1, $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function addMultiValuedAttribute(string $attributeCode, array $values): int {
        try {
            $attributeId = $this->prepareNewMultiValuedAttribute($attributeCode)->save()->getId();
            $this->addAttributeToDefaultAttributeSet($attributeCode);
            $this->addOptionsToAttribute($attributeId, $values);
            return $attributeId;
        } catch (Exception $e) {
            throw new Exception("Error adding attribute $attributeCode: " . $e->getMessage(), -1, $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function addAttributeWithOptions(string $attributeCode, array $options): int {
        try {
            $attributeId = $this->prepareNewSelectAttribute($attributeCode)->save()->getId();
            $this->addAttributeToDefaultAttributeSet($attributeCode);
            $this->addOptionsToAttribute($attributeId, $options);
            return $attributeId;
        } catch (Exception $e) {
            throw new Exception("Error adding attribute $attributeCode: " . $e->getMessage(), -1, $e);
        }
    }

    /**
     * @inheritdoc
     */
    public function addAttributeAndAssignToProduct(string $attributeCode, int $productId): int {
        try {
            $attribute = $this->prepareNewTextAttribute($attributeCode);
            $product = $this->productFactory->create()->load($productId);

            $transaction = new Transaction();
            $transaction->addObject($attribute);
            $transaction->addObject($product);
            $transaction->addCommitCallback(function () use ($product, $attributeCode) {
                $attributeValue = "$attributeCode value for product " . $product->getId();
                $this->addAttributeToDefaultAttributeSet($attributeCode);
                $this->addAttributeToProduct($product, $attributeCode, $attributeValue);
            });
            $transaction->save();

            return $this->attributeRepository->get(Product::ENTITY, $attributeCode)->getAttributeId();
        } catch (Exception $e) {
            throw new Exception("Error adding attribute $attributeCode: " . $e->getMessage(), -1, $e);
        }
    }

    private function prepareNewTextAttribute(string $attributeCode): Attribute {
        return $this->prepareNewAttribute($attributeCode, 'text', 'textarea', null, false);
    }

    private function prepareNewMultiValuedAttribute(string $attributeCode): Attribute {
        return $this->prepareNewAttribute($attributeCode, 'text', 'multiselect', ArrayBackend::class, false);
    }

    private function prepareNewSelectAttribute(string $attributeCode): Attribute {
        // attributes with options store option id as value of the attribute; options are selectable, not editable
        return $this->prepareNewAttribute($attributeCode, 'int', 'select', null, true);
    }

    private function prepareNewAttribute(string $attributeCode, string $backendType, string $frontendInput, ?string $backendModel, bool $isFilterable): Attribute {
        $displayName = implode(' ', array_map('ucfirst', explode('_', $attributeCode))); // split by underscore, capitalize words and join with space

        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $entityTypeId = $eavSetup->getEntityTypeId(Product::ENTITY);

        return $this->attributeFactory->create()
            ->setAttributeCode($attributeCode)
            ->setEntityTypeId($entityTypeId)
            ->setBackendType($backendType)
            ->setBackendModel($backendModel)
            ->setFrontendInput($frontendInput)
            ->setDefaultFrontendLabel($displayName)
            ->setIsUserDefined(true)
            ->setIsVisible(true)
            ->setIsVisibleOnFront(true)
            ->setIsFilterable($isFilterable)
            ->setUsedInProductListing(true);
    }

    private function addAttributeToDefaultAttributeSet(string $attributeCode): void {
        $eavSetup = $this->eavSetupFactory->create(['setup' => $this->moduleDataSetup]);
        $attributeSetId = $eavSetup->getAttributeSetId(Product::ENTITY, 'default');
        $attributeGroupId = $eavSetup->getAttributeGroupId(Product::ENTITY, $attributeSetId, 'general');
        $this->attributeManagement->assign(
            Product::ENTITY,
            $attributeSetId,
            $attributeGroupId,
            $attributeCode,
            999 // Sort order
        );
    }

    private function addOptionsToAttribute(int $attributeId, array $options): void {
        // Magento object model API is unclear as to how to add options to an attribute. Workaround - direct INSERT queries
        $connection = $this->resourceConnection->getConnection();
        $connection->beginTransaction();

        $sortOrder = 0;
        foreach ($options as $option) {
            $connection->insert('eav_attribute_option', [
                'attribute_id' => $attributeId,
                'sort_order' => $sortOrder++
            ]);
            $connection->insert('eav_attribute_option_value', [
                'option_id' => $connection->lastInsertId(),
                'store_id' => 0,
                'value' => $option
            ]);
        }
        $connection->commit();
    }
}