<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Client\Model;

class Data {
    public Content $content;

    public function __construct(string $content) {
        $this->content = new Content($content);
    }
}