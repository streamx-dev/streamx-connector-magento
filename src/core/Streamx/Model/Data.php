<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Streamx\Model;

class Data {
    public Content $content;

    public function __construct(string $content) {
        $this->content = new Content($content);
    }
}