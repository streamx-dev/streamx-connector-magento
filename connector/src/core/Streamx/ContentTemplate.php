<?php declare(strict_types=1);

namespace StreamX\ConnectorCore\Streamx;

class ContentTemplate {
    public string $type;
    public string $payload;

    public function getType(): string {
        return $this->type;
    }

    public function setType(string $type): void {
        $this->type = $type;
    }

    public function getPayload(): string {
        return $this->payload;
    }

    public function setPayload(string $payload): void {
        $this->payload = $payload;
    }
}