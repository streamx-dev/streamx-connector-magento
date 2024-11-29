<?php

declare(strict_types=1);

namespace Divante\VsbridgeIndexerCatalog\Model\Category;

use Divante\VsbridgeIndexerCatalog\Model\ResourceModel\Category\LoadAttributes;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class GetAttributeCodesByIds
{
    /**
     * @var LoadAttributes
     */
    private $loadAttributes;

    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(
        LoadAttributes $loadAttributes,
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
        $this->loadAttributes = $loadAttributes;
    }

    /**
     * Load attribute codes by ids
     *
     * @return array
     */
    public function execute(string $attributeIds): array
    {
        $attributes = explode(',', $attributeIds);
        $attributeCodes = [];

        foreach ($attributes as $attributeId) {
            try {
                $attribute = $this->loadAttributes->getAttributeById((int)$attributeId);
                $attributeCodes[] = $attribute->getAttributeCode();
            } catch (LocalizedException $e) {
                $this->logger->info($e->getMessage());
            }
        }

        return $attributeCodes;
    }
}
