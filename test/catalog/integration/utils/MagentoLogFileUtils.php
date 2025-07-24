<?php declare(strict_types = 1);

namespace StreamX\ConnectorCatalog\test\integration\utils;

use PHPUnit\Framework\TestCase;

/**
 * Allows retrieving published and unpublished StreamX keys.
 * The time window is: between instantiating the object, and calling its getPublishedAndUnpublishedKeys method.
 */
class MagentoLogFileUtils {

    private string $logFilePath;
    private int $logFileSize;

    public function __construct() {
        $magentoLogFilesDir = FileUtils::findFolder('magento/src/var/log');
        $this->logFilePath = "$magentoLogFilesDir/system.log";
        $this->logFileSize = filesize($this->logFilePath);
    }

    public function appendLine(string $message): void {
        file_put_contents($this->logFilePath, "$message\n", FILE_APPEND);
    }

    public function getPublishedAndUnpublishedKeys(): IngestedKeys {
        $newLogLines = $this->readNewLogFileLines();
        $result = new IngestedKeys();
        foreach ($newLogLines as $line) {
            if (str_contains($line, 'Start sending') && str_contains($line, 'with keys')) {
                $this->parseKeysAndAddToResult($line, $result);
            }
        }
        return $result;
    }

    private function readNewLogFileLines(): array {
        $file = fopen($this->logFilePath, 'r');
        fseek($file, $this->logFileSize);

        $newLines = [];
        while ($newLine = fgets($file)) {
            $newLines[] = $newLine;
        }
        fclose($file);

        return $newLines;
    }

    private function parseKeysAndAddToResult(string $line, IngestedKeys $result): void {
        $keysStartIndex = strpos($line, 'with keys [') + strlen('with keys [');
        $keysEndIndex = strpos($line, '] [] []');
        $keys = explode(',', substr($line, $keysStartIndex, $keysEndIndex - $keysStartIndex));
        if (str_contains($line, 'unpublish')) {
            $result->addUnpublishedKeys($keys);
        } else if (str_contains($line, 'publish')) {
            $result->addPublishedKeys($keys);
        }
    }

    public function verifyLoggedExactlyOnce(string ...$stringsToFind) {
        self::verifyLoggedTimes(1, ...$stringsToFind);
    }

    public function verifyLoggedTimes(int $expectedTimes, string ...$stringsToFind) {
        $actualCounts = $this->readActualCounts($stringsToFind);
        $expectedCounts = array_fill_keys($stringsToFind, $expectedTimes);
        TestCase::assertSame($expectedCounts, $actualCounts);
    }

    public function verifyLogged(string ...$stringsToFind) {
        $actualCounts = $this->readActualCounts($stringsToFind);
        foreach ($actualCounts as $string => $count) {
            TestCase::assertGreaterThan(0, $count, $string);
        }
    }

    private function readActualCounts(array $stringsToFind): array {
        $actualCounts = array_fill_keys($stringsToFind, 0);
        foreach ($this->readNewLogFileLines() as $line) {
            foreach ($stringsToFind as $string) {
                if (str_contains($line, $string)) {
                    $actualCounts[$string] = $actualCounts[$string] + 1;
                }
            }
        }
        return $actualCounts;
    }
}