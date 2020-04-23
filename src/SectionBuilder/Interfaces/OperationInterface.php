<?php

declare(strict_types=1);

/*
 * This file is part of the "Symphony CMS: Section Builder" repository.
 *
 * Copyright 2018-2020 Alannah Kearney <hi@alannahkearney.com>
 *
 * For the full copyright and license information, please view the LICENCE
 * file that was distributed with this source code.
 */

namespace pointybeard\Symphony\SectionBuilder\Interfaces;

interface OperationInterface
{
    public static function fromJsonFile(string $file, int $flags = null): array;

    public static function fromJsonString(string $string, int $flags = null): array;

    public static function fromObject(\stdClass $data, int $flags = null): array;
}
