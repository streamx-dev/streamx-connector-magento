<?php

namespace StreamX\ConnectorCatalog\test\integration\utils;

use Exception;
use PHPUnit\Framework\ExpectationFailedException;

trait ValidationFileUtils  {

    public static function readValidationFileContent(string $validationFileName): string {
        $validationFilesDir = DirectoryUtils::findFolder('resources/validation');
        return file_get_contents("$validationFilesDir/$validationFileName");
    }

    public function verifySameJsonsOrThrow(string $expectedFormattedJson, string $actualJson): void {
        $this->verifySameJsons($expectedFormattedJson, $actualJson, true);
    }

    public function verifySameJsonsSilently(string $expectedFormattedJson, string $actualJson): bool {
        return $this->verifySameJsons($expectedFormattedJson, $actualJson, false);
    }

    private function verifySameJsons(string $expectedFormattedJson, string $actualJson, bool $throwOnAssertionError): bool {
        $actualFormattedJson = JsonFormatter::formatJson($actualJson);
        try {
            $this->assertEquals(
                self::maskVariableParts($expectedFormattedJson),
                self::maskVariableParts($actualFormattedJson)
            );
            return true;
        } catch (ExpectationFailedException $e) {
            if ($throwOnAssertionError) {
                throw $e;
            }
            return false;
        }
    }

    private static function maskVariableParts(string $json): string {
        return self::standardizeNewlines(
            self::maskTimestamps($json)
        );
    }

    private static function maskTimestamps(string $json): string {
        return preg_replace('/"(created_at|updated_at)": "[^"]+"/', '"$1": "[MASKED]"', $json);
    }

    private static function standardizeNewlines(string $json): string {
        return str_replace('\r\n', '\n', $json);
    }
}