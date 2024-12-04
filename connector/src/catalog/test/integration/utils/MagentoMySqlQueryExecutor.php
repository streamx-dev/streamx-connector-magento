<?php

namespace StreamX\ConnectorCatalog\test\integration\utils;

use mysqli;

class MagentoMySqlQueryExecutor {

    private const SERVER_NAME = "127.0.0.1";

    // below settings as in magento/env/db.env file
    private const USER = "magento";
    private const PASSWORD = "magento";
    private const DB_NAME = "magento";

    /**
     * Returns the value of first field from first row, or null or error or no result data
     */
    public static function selectFirstField($selectQuery) {
        $connection = self::getConnection();

        try {
            $result = $connection->query($selectQuery);
            if ($result && $result->num_rows > 0) {
                $row = $result->fetch_row();
                $result->free();
                return $row[0];
            } else {
                return null;
            }
        } finally {
            $connection->close();
        }
    }

    public static function execute($query) {
        $connection = self::getConnection();

        try {
            $connection->query($query);
        } finally {
            $connection->close();
        }
    }

    private static function getConnection(): mysqli {
        return new mysqli(self::SERVER_NAME, self::USER, self::PASSWORD, self::DB_NAME);
    }
}