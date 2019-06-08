<?php

declare(strict_types=1);

namespace pointybeard\Symphony\SectionBuilder\Interfaces;

interface OperationInterface
{
    public static function fromJsonFile(string $file, int $flags = null): array;

    public static function fromJsonString(string $string, int $flags = null): array;

    public static function fromObject(\stdClass $data, int $flags = null): array;
}
