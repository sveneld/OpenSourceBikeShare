<?php

declare(strict_types=1);

namespace BikeShare\Rent\DTO;

use BikeShare\Rent\Enum\RentSystemType;

class RentSystemResult implements \JsonSerializable
{
    public function __construct(
        private readonly bool $error,
        private readonly string $message,
        private readonly string $code,
        private readonly array $params = [],
        private readonly RentSystemType $systemType,
    ) {
        if (trim($this->message) === '') {
            throw new \InvalidArgumentException('message cannot be empty.');
        }

        if (trim($this->code) === '') {
            throw new \InvalidArgumentException('code cannot be empty.');
        }
    }

    public function isError(): bool
    {
        return $this->error;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function getSystemType(): RentSystemType
    {
        return $this->systemType;
    }

    public function jsonSerialize(): array
    {
        return [
            'error' => $this->error,
            'message' => $this->message,
            'code' => $this->code,
            'params' => $this->params,
            'systemType' => $this->systemType->value,
        ];
    }
}
