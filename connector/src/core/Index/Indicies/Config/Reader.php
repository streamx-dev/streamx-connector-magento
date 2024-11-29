<?php

namespace StreamX\ConnectorCore\Index\Indicies\Config;

use Magento\Framework\Config\Dom;
use Magento\Framework\Config\Reader\Filesystem;
use Magento\Framework\Config\FileResolverInterface;
use Magento\Framework\Config\ValidationStateInterface;

class Reader extends Filesystem
{
    const FILE_NAME = 'streamx_indices.xml';

    /**
     * List of attributes by XPath used as ids during the file merge process.
     *
     * @var array
     */
    private $idAttributes = [
        '/indices/index' => 'identifier',
        '/indices/index/type' => 'name',
        '/indices/index/type/data_providers/data_provider' => 'name',
    ];

    public function __construct(
        FileResolverInterface $fileResolver,
        Converter $converter,
        SchemaLocator $schemaLocator,
        ValidationStateInterface $validationState,
        string $fileName = self::FILE_NAME,
        array $idAttributes = [],
        string $domDocumentClass = Dom::class,
        string $defaultScope = 'global'
    ) {
        $idAttributes = $this->idAttributes;

        parent::__construct(
            $fileResolver,
            $converter,
            $schemaLocator,
            $validationState,
            $fileName,
            $idAttributes,
            $domDocumentClass,
            $defaultScope
        );
    }
}
