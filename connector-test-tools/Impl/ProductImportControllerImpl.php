<?php

namespace StreamX\ConnectorTestTools\Impl;

use Exception;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\Directory\Read;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Filesystem\File\ReadFactory;
use Magento\ImportExport\Model\Import;
use Magento\ImportExport\Model\Import\Source\Csv;
use Magento\ImportExport\Model\ImportFactory;
use StreamX\ConnectorTestTools\Api\ProductImportControllerInterface;

class ProductImportControllerImpl implements ProductImportControllerInterface {

    private ImportFactory $importFactory;
    private DirectoryList $directoryList;
    private ReadFactory $fileReadFactory;

    public function __construct(
        ImportFactory $importFactory,
        DirectoryList $directoryList,
        ReadFactory $fileReadFactory
    ) {
        $this->importFactory = $importFactory;
        $this->directoryList = $directoryList;
        $this->fileReadFactory = $fileReadFactory;
    }

    /**
     * @inheritdoc
     */
    public function importProducts(string $csvContent, string $behavior): void {
        $import = $this->importFactory->create()
            ->setData([
                'entity' => 'catalog_product',
                'behavior' => $behavior,
                'validation_strategy' => 'validation-stop-on-errors',
            ]);

        $appDirectoryPath = $this->directoryList->getPath(DirectoryList::APP);
        $tempFileName = 'products.csv';
        $tempFilePath = "$appDirectoryPath/$tempFileName";
        $this->saveToFile($csvContent, $tempFilePath);

        $read = new Read($this->fileReadFactory, new File(), $appDirectoryPath);
        $csvSource = new Csv($tempFileName, $read);

        try {
            $this->validateCsv($import, $csvSource);
            $import->importSource();
        } finally {
            unlink($tempFilePath);
        }
    }

    private function saveToFile(string $csvContent, string $tempFilePath): void {
        $fileHandle = fopen($tempFilePath, "w");
        fwrite($fileHandle, $csvContent);
        fclose($fileHandle);
    }

    private function validateCsv(Import $import, Csv $csvSource): void {
        if (!$import->validateSource($csvSource)) {
            $errors = $import->getErrorAggregator()->getAllErrors();
            $errorMessage = "The csv file contains errors\n";
            foreach ($errors as $error) {
                $errorMessage .= sprintf(" - at row %d, column %s: %s\n", $error->getRowNumber(), $error->getColumnName(), $error->getErrorMessage());
            }
            throw new Exception($errorMessage);
        }
    }
}