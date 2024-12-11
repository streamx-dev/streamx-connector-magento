<?php

namespace StreamX\ConnectorCatalog\Rest;

use Exception;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Exception\NoSuchEntityException;
use StreamX\ConnectorCatalog\Api\Rest\EditProductEndpointInterface;

class EditProductEndpoint extends AbstractHelper implements EditProductEndpointInterface {

    private ProductRepositoryInterface $productRepository;

    public function __construct(Context $context, ProductRepositoryInterface $productRepository) {
        parent::__construct($context);
        $this->productRepository = $productRepository;
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
            throw new Exception("Product with ID $productId does not exist", -1, $e);
        } catch (Exception $e) {
            throw new Exception("Error renaming product with ID $productId", -2, $e);
        }
    }
}