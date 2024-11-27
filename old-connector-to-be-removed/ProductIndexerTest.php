<?php declare(strict_types=1);

namespace Streamx\Connector\Model\Indexer\Tests;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\ProductRepository;
use PHPUnit\Framework\TestCase;
use ProductIndexer;
use Psr\Log\LoggerInterface;

// TODO: adjust test to recent changes
class ProductIndexerTest extends TestCase {

    private const INGESTION_BASE_URL = "http://localhost:8080";
    private const DELIVERY_BASE_URL = "http://localhost:8081";

    /** @test */
    public function shouldPublishEditedProductsToStreamX() {
        // given
        $logger = $this->createMock(LoggerInterface::class);

        $product1 = $this->createMock(ProductInterface::class);
        $product1->method('getId')->willReturn(1);

        $product2 = $this->createMock(ProductInterface::class);
        $product2->method('getId')->willReturn(2);

        $productRepository = $this->createMock(ProductRepository::class);
        $productRepository->method('getById')->willReturnCallback(function ($input) use($product1, $product2) {
            switch ($input) {
                case 1:
                    return $product1;
                case 2:
                    return $product2;
                default:
                    return null;
            }
        });

        // when
        $indexer = new ProductIndexer($logger, $productRepository);
        $indexer->ingestionBaseUrl = self::INGESTION_BASE_URL;
        $indexer->executeList([1, 2]);

        // then
        $this->assertPageIsPublished('product_1');
        $this->assertPageIsPublished('product_2');
    }

    private function assertPageIsPublished(string $key) {
        $url = self::DELIVERY_BASE_URL . '/' . $key;

        $startTime = time();
        while (time() - $startTime < 3) {
            $response = @file_get_contents($url);
            if ($response !== false) {
                $this->assertStringContainsString('Admin has edited a Product at', $response);
                return;
            }
            usleep(100000); // sleep for 100 milliseconds
        }

        $this->fail("$url: page not found");
    }

}