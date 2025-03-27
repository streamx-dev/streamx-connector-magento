<?php

namespace StreamX\ConnectorCatalog\test\integration;

use PHPUnit\Framework\TestCase;
use stdClass;
use StreamX\ConnectorCatalog\test\integration\utils\ValidationFileUtils;
use JsonSchema\Validator;

class JsonSchemaValidationTest extends TestCase {

    use ValidationFileUtils;

    private const SCHEMA_URL = 'https://raw.githubusercontent.com/streamx-dev/streamx-commerce-accelerator/refs/heads/main/spec/model/SxModel.json';
    private const SCHEMA_JSON_FILE = __DIR__ . '/schema.json';
    private static object $schema;

    public static function setUpBeforeClass(): void {
        self::downloadSchemaToLocalFile();
        self::$schema = (object) ['$ref' => 'file://' . self::SCHEMA_JSON_FILE];
    }

    public static function tearDownAfterClass(): void {
        unlink(self::SCHEMA_JSON_FILE);
    }

    private static function downloadSchemaToLocalFile(): void {
        file_put_contents(self::SCHEMA_JSON_FILE,
            file_get_contents(JsonSchemaValidationTest::SCHEMA_URL)
        );
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
        $validator = new Validator();
        $validatedContent = self::readJson($validationFilePath);

        // when
        $validator->validate($validatedContent, self::$schema);
        $errors = $validator->getErrors();

        // then
        if ($validator->isValid()) {
            $this->assertEmpty($errors);
        } else {
            $errorsCount = count($errors);
            $errorsJson = json_encode($errors, JSON_PRETTY_PRINT);
            $this->fail("Detected $errorsCount errors in file $validationFilePath:\n$errorsJson");
        }
    }

    private static function readJson(string $url): stdClass {
        return json_decode(file_get_contents($url));
    }
}