<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Client\Model;

class Content {
    public string $bytes;

    public function __construct(string $bytes) {
        $this->bytes = $bytes;
    }
}