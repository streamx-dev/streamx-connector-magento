<?php

namespace StreamX\ConnectorCatalog\Rest;

use Exception;
use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use StreamX\ConnectorCatalog\Api\Rest\EditCategoryEndpointInterface;

class EditCategoryEndpoint extends AbstractHelper implements EditCategoryEndpointInterface {

    private CategoryRepositoryInterface $categoryRepository;

    public function __construct(Context $context, CategoryRepositoryInterface $categoryRepository) {
        parent::__construct($context);
        $this->categoryRepository = $categoryRepository;
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
            throw new Exception("Category with ID $categoryId does not exist", -1, $e);
        } catch (Exception $e) {
            throw new Exception("Error renaming category with ID $categoryId", -2, $e);
        }
    }
}