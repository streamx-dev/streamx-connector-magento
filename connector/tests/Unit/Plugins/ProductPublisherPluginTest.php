<?php declare(strict_types=1);

namespace Streamx\Connector\Plugins\Tests;

use Streamx\Connector\Plugins\ProductPublisherPlugin;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Controller\Adminhtml\Product\Edit;
use Magento\Catalog\Model\ProductRepository;
use Magento\Framework\App\RequestInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ProductPublisherPluginTest extends TestCase {

    private const INGESTION_BASE_URL = "http://localhost:8080";
    private const DELIVERY_BASE_URL = "http://localhost:8081";

    /** @test */
    public function testPlugin() {
        // given
        $logger = $this->createMock(LoggerInterface::class);

        $product = $this->createMock(ProductInterface::class);
        $proceed = function() use($product) { return $product; };

        $productRepository = $this->createMock(ProductRepository::class);
        $productRepository->method('getById')->willReturn($product);

        $request = $this->createMock(RequestInterface::class);
        $edit = $this->createMock(Edit::class);
        $edit->method('getRequest')->willReturn($request);

        // when
        $plugin = new ProductPublisherPlugin($logger, $productRepository);
        $plugin->ingestionBaseUrl = self::INGESTION_BASE_URL;
        $result = $plugin->aroundExecute($edit, $proceed);

        // then
        $this->assertEquals($product, $result);
        $this->assertPageIsPublished('key-from-magento-connector');
    }

    private function assertPageIsPublished(string $key) {
        $url = self::DELIVERY_BASE_URL . '/' . $key;

        $startTime = time();
        while (time() - $startTime < 3) {
            $response = @file_get_contents($url);
            if ($response !== false) {
                echo "Response: $response\n";
                return;
            }
            usleep(100000); // sleep for 100 milliseconds
        }

        $this->fail("$url: page not found");
    }

}