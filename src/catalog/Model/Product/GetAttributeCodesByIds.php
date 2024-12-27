<?php declare(strict_types=1);

namespace StreamX\ConnectorCatalog\Model\Product;

use StreamX\ConnectorCatalog\Model\ResourceModel\Product\LoadAttributes;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

class GetAttributeCodesByIds
{
    private LoadAttributes $loadAttributes;
    private LoggerInterface $logger;

    public function __construct(
        LoadAttributes $loadAttributes,
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
        $this->loadAttributes = $loadAttributes;
    }

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
