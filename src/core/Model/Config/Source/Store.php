<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Model\Config\Source;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\OptionSourceInterface;
use Magento\Store\Model\ResourceModel\Store\Collection;
use Magento\Store\Model\ResourceModel\Store\CollectionFactory;

class Store implements OptionSourceInterface
{
    private CollectionFactory $storesFactory;
    private RequestInterface $request;

    public function __construct(CollectionFactory $storesFactory, RequestInterface $request) {
        $this->storesFactory = $storesFactory;
        $this->request = $request;
    }

    /**
     * @inheritDoc
     */
    public function toOptionArray(): array {
        $storesToSelect = $this->storesFactory->create();
        $this->filterByWebsite($storesToSelect);
        return $this->mapToOptions($storesToSelect->load());
    }

    private function filterByWebsite(Collection $storesToSelect): void {
        $websiteId = $this->request->getParam('website');
        if ($websiteId) {
            // the code is currently executed to list stores available to index for a specific website - limit search results:
            $storesToSelect->addWebsiteFilter($websiteId);
        }
    }

    private function mapToOptions(Collection $stores): array {
        $options[] = [
            'value' => '',
            'label' => '[None]',
        ];

        foreach ($stores as $store) {
            $options[] = [
                'value' => (string)$store->getData('store_id'),
                'label' => (string)$store->getData('name')
            ];
        }
        return $options;
    }
}
