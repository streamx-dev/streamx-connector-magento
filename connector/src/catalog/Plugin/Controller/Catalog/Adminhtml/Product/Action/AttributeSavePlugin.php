<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Plugin\Controller\Catalog\Adminhtml\Product\Action;

use StreamX\ConnectorCatalog\Model\Indexer\ProductProcessor;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Catalog\Controller\Adminhtml\Product\Action\Attribute\Save;
use Magento\Catalog\Helper\Product\Edit\Action\Attribute;

class AttributeSavePlugin
{
    /**
     * @var Attribute
     */
    private $attributeHelper;

    /**
     * @var ProductProcessor
     */
    private $processor;

    public function __construct(Attribute $attributeHelper, ProductProcessor $processor)
    {
        $this->attributeHelper = $attributeHelper;
        $this->processor = $processor;
    }

    /**
     * @return Redirect
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterExecute(Save $subject, Redirect $result)
    {
        $productIds = $this->attributeHelper->getProductIds();

        $this->processor->reindexList($productIds);

        return $result;
    }
}
