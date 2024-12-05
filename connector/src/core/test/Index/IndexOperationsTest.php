<?php

use StreamX\ConnectorCore\Api\Client\ClientInterface;
use StreamX\ConnectorCore\Config\OptimizationSettings;
use StreamX\ConnectorCore\Index\IndexOperations;
use StreamX\ConnectorCore\Index\IndexSettings;
use StreamX\ConnectorCore\Api\BulkResponseInterfaceFactory as BulkResponseFactory;
use StreamX\ConnectorCore\Api\BulkRequestInterfaceFactory as BulkRequestFactory;
use StreamX\ConnectorCore\Api\IndexInterfaceFactory as IndexFactory;
use StreamX\ConnectorCore\Index\Index;
use StreamX\ConnectorCore\Streamx\ClientResolver;
use PHPUnit\Framework\TestCase;
use Magento\Store\Model\Store;

class IndexOperationsTest extends TestCase
{
    /**
     * @var Store|PHPUnit_Framework_MockObject_MockObject
     */
    private $storeMock;

    /**
     * @var IndexSettings|PHPUnit_Framework_MockObject_MockObject
     */
    private $esIndexSettingsMock;

    /**
     * @var ClientResolver
     */
    private $clientResolverMock;

    /**
     * @var IndexFactory
     */
    private $indexFactoryMock;

    /**
     * @var BulkResponseFactory
     */
    private $bulkResponseFactoryMock;

    /**
     * @var BulkRequestFactory
     */
    private $bulkRequestFactoryMock;

    /**
     * @var IndexOperations
     */
    private $indexOperations;

    /** @var PHPUnit_Framework_MockObject_MockObject  */
    private $clientMock;

    /** @var OptimizationSettings|PHPUnit_Framework_MockObject_MockObject */
    private $optimizationSettingsMock;

    /** @var array[][]  */
    private $indicesXmlConfiguration  = [
        IndexSettings::INDEX_NAME_PREFIX => [
            'types' => []
        ]
    ];

    protected function setUp(): void
    {
        $this->storeMock = $this->getMockBuilder(Store::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->esIndexSettingsMock = $this->getMockBuilder(IndexSettings::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->bulkRequestFactoryMock = $this->getMockBuilder(BulkRequestFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->bulkResponseFactoryMock = $this->getMockBuilder(BulkResponseFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->indexFactoryMock = $this->getMockBuilder(IndexFactory::class)
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $this->clientMock = $this->getMockBuilder(
            ClientInterface::class
        )->disableOriginalConstructor()->getMock();

        $this->clientResolverMock = $this->getMockBuilder(ClientResolver::class)
                ->disableOriginalConstructor()
                ->getMock();

        $this->optimizationSettingsMock = $this->getMockBuilder(OptimizationSettings::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->indexOperations = new IndexOperations(
            $this->clientResolverMock,
            $this->bulkResponseFactoryMock,
            $this->bulkRequestFactoryMock,
            $this->esIndexSettingsMock,
            $this->indexFactoryMock,
            $this->optimizationSettingsMock
        );
    }

    public function testGetExistingIndex()
    {
        $name = IndexSettings::INDEX_NAME_PREFIX;

        $indexMock = new Index(
            $name,
            []
        );

        $this->indexFactoryMock
            ->method('create')
            ->with([
                'name' => $name,
                'types' => [],
            ])
            ->willReturn($indexMock);

        $this->esIndexSettingsMock->method('getIndicesConfig')->willReturn($this->indicesXmlConfiguration);
        $this->esIndexSettingsMock->method('createIndexName')->willReturn($name);
        $this->clientResolverMock->method('getClient')->with(1)->willReturn($this->clientMock);
        $this->storeMock->method('getId')->willReturn(1);

        $index = $this->indexOperations->getIndex($this->storeMock);
        $this->assertEquals($indexMock, $index);
    }

    public function testCreateNewIndex()
    {
        $this->storeMock->method('getId')->willReturn(1);

        $name = IndexSettings::INDEX_NAME_PREFIX;

        $indexMock = new Index(
            $name,
            []
        );

        $this->indexFactoryMock
            ->method('create')
            ->with([
                'name' => $name,
                'types' => [],
            ])
            ->willReturn($indexMock);

        $this->esIndexSettingsMock->method('getIndicesConfig')->willReturn($this->indicesXmlConfiguration);
        $this->esIndexSettingsMock->method('createIndexName')->willReturn($name);
        $this->clientResolverMock->method('getClient')->with(1)->willReturn($this->clientMock);

        $index = $this->indexOperations->createIndex($this->storeMock);
        $this->assertEquals($indexMock, $index);
    }
}
