<?php

declare(strict_types=1);

namespace App\Contracts;

interface RedisServerInterface
{
    public function setIfNotExists(string $key, mixed $value, int $ttl): bool;

    public function delete(string $key): void;

    /**
     * @param  array<string, string>  $fields
     */
    public function setHashFields(string $key, array $fields): void;

    /**
     * @return array<string, string>
     */
    public function getHashAll(string $key): array;

    public function setExpiry(string $key, int $seconds): void;
}
