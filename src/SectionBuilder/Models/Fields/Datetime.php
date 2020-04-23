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

class Datetime extends AbstractField implements FieldInterface
{
    const TYPE = 'datetime';
    const TABLE = 'tbl_fields_datetime';

    public static function getFieldMappings(): \stdClass
    {
        return (object) array_merge((array) parent::getFieldMappings(), [
            'prepopulate' => [
                'name' => 'prePopulate',
                'flags' => self::FLAG_BOOL,
            ],

            'format' => [
                'name' => 'format',
                'flags' => self::FLAG_STR | self::FLAG_NULL,
            ],

            'time' => [
                'name' => 'time',
                'flags' => self::FLAG_BOOL,
            ],

            'range' => [
                'name' => 'range',
                'flags' => self::FLAG_BOOL,
            ],

            'multiple' => [
                'name' => 'multiple',
                'flags' => self::FLAG_BOOL,
            ],
        ]);
    }

    public function getEntriesDataCreateTableSyntax(): string
    {
        return sprintf(
            'CREATE TABLE IF NOT EXISTS `tbl_entries_data_%d` (
                `id` int(11) unsigned NOT NULL auto_increment,
                `entry_id` int(11) unsigned NOT NULL,
                `start` datetime NOT NULL,
                `end` datetime NOT NULL,
                PRIMARY KEY (`id`),
                KEY `entry_id` (`entry_id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;',
            (int) $this->id->value
        );
    }

    protected static function boolToTinyInt(bool $value): int
    {
        return true == $value ? 1 : 0;
    }

    public function getDatabaseReadyData(): array
    {
        return [
            'field_id' => (int) $this->id->value,
            'pre_populate' => self::boolToTinyInt($this->prePopulate->value),
            'range' => self::boolToTinyInt($this->range->value),
            'time' => self::boolToTinyInt($this->time->value),
            'multiple' => self::boolToTinyInt($this->multiple->value),
        ];
    }
}
