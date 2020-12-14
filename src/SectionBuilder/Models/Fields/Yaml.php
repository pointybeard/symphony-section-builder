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

namespace pointybeard\Symphony\SectionBuilder\Models\Fields;

use pointybeard\Symphony\SectionBuilder\AbstractField;
use pointybeard\Symphony\SectionBuilder\Interfaces\FieldInterface;

class Yaml extends AbstractField implements FieldInterface
{
    const TYPE = 'yaml';
    const TABLE = 'tbl_fields_yaml';

    public static function getFieldMappings(): \stdClass
    {
        return (object) array_merge((array) parent::getFieldMappings(), [
            'size' => [
                'name' => 'size',
                'flags' => self::FLAG_INT,
            ],
        ]);
    }

    public function getEntriesDataCreateTableSyntax(): string
    {
        return sprintf(
            'CREATE TABLE `tbl_entries_data_%d` (
              `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `entry_id` int(11) unsigned NOT NULL,
              `value` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL,
              PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;',
            (int) $this->id->value
        );
    }

    public function getDatabaseReadyData(): array
    {
        return [
            'field_id' => (int) $this->id->value,
            'size' => (int) $this->size->value
        ];
    }
}
