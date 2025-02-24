<?php

namespace StreamX\ConnectorCatalog\test\integration;

use PHPUnit\Framework\TestCase;
use StreamX\ConnectorCatalog\test\integration\utils\ValidationFileUtils;
use Swaggest\JsonSchema\InvalidValue;
use Swaggest\JsonSchema\Schema;

class JsonSchemaValidation extends TestCase {

    use ValidationFileUtils;

    private const SCHEMA_URL = 'https://raw.githubusercontent.com/streamx-dev/streamx-commerce-accelerator/refs/heads/main/spec/model/SxModel.json';

    public function validationFilesProvider(): array {
        return array_map(function ($path) {
            return [$path];
        }, $this->readPathsOfAllValidationFiles());
    }

    /**
     * @test
     * @dataProvider validationFilesProvider
     */
    public function testAllValidationFilesShouldMatchSchema(string $validationFilePath) {
        $schemaJson = file_get_contents(self::SCHEMA_URL);

        $validationFileJson = file_get_contents($validationFilePath);

        // TODO: list to changes to perform in https://github.com/streamx-dev/streamx-commerce-accelerator/blob/main/spec/model/SxModel.json:
        // 1. Wrong name of one of the SxAttribute required fields
        $schemaJsonLines = explode("\n", $schemaJson);
        $schemaJsonLines[170] = str_replace($schemaJsonLines[170], '"value"', '"values"');
        $schemaJson = implode("\n", $schemaJsonLines);

        // 2. Remove line that made the schema useful only for Products validation
        $schemaJson = str_replace('"$ref": "#/definitions/SxProduct",', '', $schemaJson);

        try {
            $result = Schema::import(
                json_decode($schemaJson),
            )->in(
                json_decode($validationFileJson),
            );
            $this->assertNotNull($result);
        } catch (InvalidValue $e) {
            $this->fail("Error in file $validationFilePath:\n {$e->getMessage()} at line {$e->getLine()}");
        }
    }
}