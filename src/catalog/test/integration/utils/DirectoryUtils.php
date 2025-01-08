<?php

namespace StreamX\ConnectorCatalog\test\integration\utils;

use Exception;

final class DirectoryUtils {

    /**
     * Goes up from the current dir, until finding folder with the given name, and returns its absolute path
     */
    public static function findFolder(string $folder): string {
        $currentDir = __DIR__; // Start from the current directory

        while (true) {
            if (is_dir("$currentDir/$folder")) {
                return "$currentDir/$folder";
            }

            $parentDir = dirname($currentDir);

            if ($parentDir === $currentDir) { // root dir reached
                break;
            }

            $currentDir = $parentDir;
        }

        throw new Exception("$folder folder not found");
    }
}