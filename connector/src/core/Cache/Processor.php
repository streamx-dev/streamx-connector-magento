<?php

namespace StreamX\ConnectorCore\Cache;

use Magento\Framework\HTTP\Adapter\CurlFactory;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Psr\Log\LoggerInterface;

class Processor
{
    /**
     * Mapping elastic type to cache tag used by vsf
     * @var array
     */
    private $defaultCacheTags = [
        'category' => 'C',
        'product' => 'P',
    ];

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var array
     */
    private $cacheTags;

    /**
     * @var CurlFactory
     */
    private $curlFactory;

    /**
     * @var EventManager
     */
    private $eventManager;

    public function __construct(
        CurlFactory $curlFactory,
        ConfigInterface $config,
        EventManager $manager,
        LoggerInterface $logger
    ) {
        $this->eventManager = $manager;
        $this->curlFactory = $curlFactory;
        $this->logger = $logger;
        $this->config = $config;
    }

    /**
     * @return $this
     */
    public function cleanCacheByDocIds(int $storeId, string $dataType, array $entityIds)
    {
        if ($this->config->clearCache($storeId)) {
            if (!empty($entityIds)) {
                $this->cleanCacheInBatches($storeId, $dataType, $entityIds);
            } else {
                $cacheTags = $this->getCacheTags();

                if (isset($cacheTags[$dataType])) {
                    $this->cleanCacheByTags($storeId, [$dataType]);
                }
            }
        }

        return $this;
    }

    public function cleanCacheInBatches(int $storeId, string $dataType, array $entityIds)
    {
        $batches = [$entityIds];

        foreach ($batches as $batch) {
            $cacheInvalidateUrl = $this->getCacheInvalidateUrl($dataType, $entityIds);
            $this->logger->debug('BATCHES ' . implode(', ', $batch));
            $this->logger->debug('Cache invalidate url ' . $cacheInvalidateUrl);
            $this->logger->debug('Store id ' . $storeId);
            $this->logger->debug('Data type ' . $dataType);

            try {
                $this->call($storeId, $cacheInvalidateUrl);
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }
    }

    public function cleanCacheByTags(int $storeId, array $tags)
    {
        $storeId = (int) $storeId;

        if ($this->config->clearCache($storeId)) {
            $cacheTags = implode(',', $tags);
            $cacheInvalidateUrl = $this->getInvalidateCacheUrl() . $cacheTags;

            try {
                $this->call($storeId, $cacheInvalidateUrl);
            } catch (\Exception $e) {
                $this->logger->error($e->getMessage());
            }
        }
    }

    private function call(string $storeId, string $uri)
    {
        $config = $this->config->getConnectionOptions($storeId);
        /** @var \Magento\Framework\HTTP\Adapter\Curl $curl */
        $curl = $this->curlFactory->create();
        $curl->setConfig($config);
        $curl->write(\Zend_Http_Client::GET, $uri, '1.0');
        $response = $curl->read();

        if ($response !== false && !empty($response)) {
            $httpCode = \Zend_Http_Response::extractCode($response);

            if ($httpCode !== 200) {
                $response = \Zend_Http_Response::extractBody($response);
                $this->logger->error($response);
            }
        } else {
            $this->logger->error('Problem with clearing VSF cache.');
        }
    }

    private function getCacheInvalidateUrl(string $type, array $ids): string
    {
        $fullUrl = $this->getInvalidateCacheUrl();
        $params = $this->prepareTagsByDocIds($type, $ids);
        $fullUrl .= $params;

        return $fullUrl;
    }

    private function getInvalidateCacheUrl(): string
    {
        return 'http://localhost:3000/invalidate?key=aeSu7aip&tag=';
    }

    public function prepareTagsByDocIds(string $type, array $ids): string
    {
        $params = '';
        $cacheTags = $this->getCacheTags();

        if (isset($cacheTags[$type])) {
            $cacheTag = $cacheTags[$type];
            $count = count($ids);

            foreach ($ids as $key => $id) {
                $params .= $cacheTag . $id;

                if ($key !== ($count - 1)) {
                    $params .= ',';
                }
            }
        }

        return $params;
    }

    public function getCacheTags(): array
    {
        if (null === $this->cacheTags) {
            $tagsDataObject = new \Magento\Framework\DataObject();
            $tagsDataObject->setData('items', $this->defaultCacheTags);
            $this->eventManager->dispatch(
                'vsf_prepare_cache_tags',
                ['cache_tags' => $tagsDataObject]
            );
            $this->cacheTags = $tagsDataObject->getData('items');
        }

        return $this->cacheTags;
    }
}
