<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Plugin\Controller\Catalog\Adminhtml\Product\Action;

use StreamX\ConnectorCatalog\Model\Indexer\ProductProcessor;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Catalog\Controller\Adminhtml\Product\Action\Attribute\Save;
use Magento\Catalog\Helper\Product\Edit\Action\Attribute;

class AttributeSavePlugin
{
    private Attribute $attributeHelper;
    private ProductProcessor $processor;

    public function __construct(Attribute $attributeHelper, ProductProcessor $processor)
    {
        $this->attributeHelper = $attributeHelper;
        $this->processor = $processor;
    }

    /**
     * Executed when Magento UI Admin selects multiple products in the Products Table and performs a mass update of their attributes
     */
    public function afterExecute(Save $subject, Redirect $result): Redirect
    {
        $productIds = $this->attributeHelper->getProductIds();

        $this->processor->reindexList($productIds);

        return $result;
    }
}
