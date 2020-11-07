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

use pointybeard\Symphony\SectionBuilder;

interface FieldInterface
{
    public function commit(): SectionBuilder\AbstractTableModel;

    public function getEntriesDataCreateTableSyntax(): string;

    public function hasAssociations(): bool;

    public static function getFieldMappings(): \stdClass;

    public function getDatabaseReadyData(): array;
}
