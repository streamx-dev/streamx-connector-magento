<?php

namespace StreamX\ConnectorCatalog\test\integration;

use PHPUnit\Framework\TestCase;
use stdClass;
use StreamX\ConnectorCatalog\test\integration\utils\ValidationFileUtils;
use Swaggest\JsonSchema\InvalidValue;
use Swaggest\JsonSchema\Schema;

class JsonSchemaValidationTest extends TestCase {

    use ValidationFileUtils;

    private const SCHEMA_URL = 'https://raw.githubusercontent.com/streamx-dev/streamx-commerce-accelerator/refs/heads/main/spec/model/SxModel.json';
    private static stdClass $schema;

    public static function setUpBeforeClass(): void {
        self::$schema = self::readJson(self::SCHEMA_URL);
    }

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
        // given
        $validatedContent = self::readJson($validationFilePath);

        try {
            // when
            $result = Schema::import(self::$schema)->in($validatedContent);

            // then
            $this->assertNotNull($result);
        } catch (InvalidValue $e) {
            $this->fail("Error in file $validationFilePath:\n {$e->getMessage()} at line {$e->getLine()}");
        }
    }

    private static function readJson(string $url): stdClass {
        return json_decode(file_get_contents($url));
    }
}