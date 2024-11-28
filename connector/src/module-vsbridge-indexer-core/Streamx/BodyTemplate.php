<?php declare(strict_types=1);

namespace Divante\VsbridgeIndexerCore\Streamx;

class BodyTemplate {
    public string $key = "";
    public string $type = "";
    public ContentTemplate $content;
    public array $dependencies = [];

    public function getKey(): string {
        return $this->key;
    }

    public function setKey(string $key): void {
        $this->key = $key;
    }

    public function getType(): string {
        return $this->type;
    }

    public function setType(string $type): void {
        $this->type = $type;
    }

    public function getContent(): ContentTemplate {
        return $this->content;
    }

    public function setContent(ContentTemplate $content): void {
        $this->content = $content;
    }

    public function getDependencies(): array {
        return $this->dependencies;
    }

    public function setDependencies(array $dependencies): void {
        $this->dependencies = $dependencies;
    }
}