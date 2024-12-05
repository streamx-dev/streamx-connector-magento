<?php

declare(strict_types=1);

use StreamX\ConnectorCore\Config\IndicesSettings;
use StreamX\ConnectorCore\Index\IndexSettings;
use StreamX\ConnectorCore\Index\Indicies\Config;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Intl\DateTimeFactory;

/**
 * Responsible for testing \StreamX\ConnectorCore\Index\IndexSettings
 */
class IndexSettingsTest extends TestCase
{
    /**
     * @var PHPUnit_Framework_MockObject_MockObject
     */
    private $indicesSettingsMock;

    /**
     * @var IndicesSettings|PHPUnit_Framework_MockObject_MockObject
     */
    private $configurationSettings;

    /**
     * @var Store|PHPUnit_Framework_MockObject_MockObject
     */
    private $storeMock;

    /**
     * @var PHPUnit_Framework_MockObject_MockObject
     */
    private $storeManagerMock;

    /**
     * @var IndexSettings
     */
    private $esIndexSettings;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->storeManagerMock = $this->getMockBuilder(
            StoreManagerInterface::class)
            ->disableOriginalConstructor()
            ->setMethods([])
            ->getMockForAbstractClass();

        $this->storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->indicesSettingsMock = $this->createMock(IndicesSettings::class);
        $this->configurationSettings = $this->createMock(Config::class);

        $this->esIndexSettings = new IndexSettings(
            $this->storeManagerMock,
            $this->configurationSettings,
            $this->indicesSettingsMock,
            new DateTimeFactory()
        );
    }

    /**
     * @dataProvider provideStores
     */
    public function testGetIndexAlias(int $storeId)
    {
        $indexPrefix = 'streamx_storefront_catalog';
        $this->indicesSettingsMock->method('addIdentifierToDefaultStoreView')->willReturn(true);
        $this->indicesSettingsMock->method('getIndexNamePrefix')->willReturn($indexPrefix);
        $this->storeMock->method('getId')->willReturn($storeId);

        $expectedAlias = strtolower(sprintf('%s_%d', $indexPrefix, $storeId));

        $this->assertStringStartsWith(
            $expectedAlias,
            $this->esIndexSettings->createIndexName($this->storeMock)
        );
    }

    public function provideStores(): array
    {
        return [
            [1],
            [2],
            [3],
        ];
    }
}
