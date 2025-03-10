<?php

namespace StreamX\ConnectorTestTools\Impl;

use Exception;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Directory\Read;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Filesystem\File\ReadFactory;
use Magento\ImportExport\Model\Import\ErrorProcessing\ProcessingError;
use Magento\ImportExport\Model\Import\Source\Csv;
use Magento\ImportExport\Model\ImportFactory;
use Psr\Log\LoggerInterface;
use StreamX\ConnectorTestTools\Api\EntityAddControllerInterface;
use StreamX\ConnectorTestTools\Api\TestDataControllerInterface;

class TestDataControllerImpl implements TestDataControllerInterface {

    private const PRODUCTS_CSV_FILE_PATH_RELATIVE_TO_APP_DIR = 'code/StreamX/ConnectorTestTools/resources/magento-products.csv';
    private const CATEGORY_TO_ADD = 'Furniture';

    private LoggerInterface $logger;
    private ImportFactory $importFactory;
    private DirectoryList $directoryList;
    private ReadFactory $fileReadFactory;
    private EntityAddControllerInterface $entityAddController;

    public function __construct(
        LoggerInterface $logger,
        ImportFactory $importFactory,
        DirectoryList $directoryList,
        ReadFactory $fileReadFactory,
        EntityAddControllerInterface $entityAddController
    ) {
        $this->logger = $logger;
        $this->importFactory = $importFactory;
        $this->directoryList = $directoryList;
        $this->fileReadFactory = $fileReadFactory;
        $this->entityAddController = $entityAddController;
    }

    /**
     * @inheritdoc
     */
    public function importTestProducts(): void {
        // TODO: Currently all products in the file have Furniture category. Replace with: first read categories from the products csv, and create them in Magento
        // TODO: also first read all attributes from the products csv, and create them in Magento
        // TODO: disable downloading images for the products
        $this->entityAddController->addCategory(self::CATEGORY_TO_ADD);

        $import = $this->importFactory->create();
        $import->setData([
            'entity' => 'catalog_product',
            'behavior' => 'add_update',
            'validation_strategy' => 'validation-stop-on-errors',
        ]);

        $appDirectory = $this->directoryList->getPath(DirectoryList::APP);
        $read = new Read($this->fileReadFactory, new File(), $appDirectory);
        $csvSource = new Csv(self::PRODUCTS_CSV_FILE_PATH_RELATIVE_TO_APP_DIR, $read);

        if (!$import->validateSource($csvSource)) {
            $errors = $import->getErrorAggregator()->getAllErrors();
            $errorMessage = "The csv file contains errors\n";

            /** @var $error ProcessingError */
            foreach ($errors as $error) {
                $errorMessage .= sprintf(" - at row %d, column %s: %s\n", $error->getRowNumber(), $error->getColumnName(), $error->getErrorMessage());
            }

            throw new Exception($errorMessage);
        }

        if ($import->importSource()) {
            $import->invalidateIndex();
        }

        $this->logger->info('Finished importing products from ' . self::PRODUCTS_CSV_FILE_PATH_RELATIVE_TO_APP_DIR);
    }
}
