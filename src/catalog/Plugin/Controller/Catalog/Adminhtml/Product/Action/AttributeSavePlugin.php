<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Plugin\Controller\Catalog\Adminhtml\Product\Action;

use Magento\Backend\Model\View\Result\Redirect;
use Magento\Catalog\Controller\Adminhtml\Product\Action\Attribute\Save;
use Magento\Catalog\Helper\Product\Edit\Action\Attribute;
use StreamX\ConnectorCatalog\Indexer\ProductIndexer;

class AttributeSavePlugin
{
    private Attribute $attributeHelper;
    private ProductIndexer $indexer;

    public function __construct(Attribute $attributeHelper, ProductIndexer $indexer)
    {
        $this->attributeHelper = $attributeHelper;
        $this->indexer = $indexer;
    }

    /**
     * Executed when Magento UI Admin selects multiple products in the Products Table and performs a mass update of their attributes
     */
    public function afterExecute(Save $subject, Redirect $result): Redirect
    {
        $productIds = $this->attributeHelper->getProductIds();

        $this->indexer->reindexList($productIds);

        return $result;
    }
}
