<?php

declare(strict_types=1);

namespace FINDOLOGIC\Shopware6Common\Export\Errors;

class ProductError
{
    public function __construct(
        private readonly string $id,
        private array $errors,
    ) {
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'errors' => $this->errors,
        ];
    }

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return string[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function addErrors(array $errors): self
    {
        $this->errors[] = array_merge($this->errors, $errors);

        return $this;
    }
}
