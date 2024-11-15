<?php declare(strict_types=1);

namespace Streamx\Connector\Plugins\Tests;

use Streamx\Connector\Plugins\ProductPublisherPlugin;
use Magento\Catalog\Controller\Adminhtml\Product\Edit;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ProductPublisherPluginTest extends TestCase {

    private const INGESTION_BASE_URL = "http://localhost:8080";
    private const DELIVERY_BASE_URL = "http://localhost:8081";

    /** @test */
    public function testPlugin() {
        // given
        $edit = $this->createMock(Edit::class);
        $myClosure = function() {
            return 'abc';
        };
        $logger = $this->createMock(LoggerInterface::class);

        // when
        $plugin = new ProductPublisherPlugin($logger);
        $plugin->ingestionBaseUrl = self::INGESTION_BASE_URL;
        $result = $plugin->aroundExecute($edit, $myClosure);

        // then
        $this->assertEquals('abc', $result);
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