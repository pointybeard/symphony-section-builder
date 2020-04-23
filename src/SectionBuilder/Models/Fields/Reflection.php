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

class Reflection extends AbstractField implements FieldInterface
{
    const TYPE = 'reflection';
    const TABLE = 'tbl_fields_reflection';

    public static function getFieldMappings(): \stdClass
    {
        return (object) array_merge((array) parent::getFieldMappings(), [
            'xsltfile' => [
                'name' => 'xsltFile',
                'flags' => self::FLAG_STR | self::FLAG_NULL,
            ],

            'expression' => [
                'name' => 'expression',
                'flags' => self::FLAG_STR | self::FLAG_NULL,
            ],

            'formatter' => [
                'name' => 'formatter',
                'flags' => self::FLAG_STR | self::FLAG_NULL,
            ],

            'override' => [
                'name' => 'override',
                'flags' => self::FLAG_BOOL,
            ],

            'fetch_associated_counts' => [
                'name' => 'fetchAssociatedCounts',
                'flags' => self::FLAG_BOOL,
            ],

            'hide' => [
                'name' => 'hide',
                'flags' => self::FLAG_BOOL,
            ],
        ]);
    }

    public function getEntriesDataCreateTableSyntax(): string
    {
        return sprintf(
            'CREATE TABLE `tbl_entries_data_%d` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `entry_id` int(11) unsigned NOT NULL,
                `handle` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                `value` text COLLATE utf8_unicode_ci,
                `value_formatted` text COLLATE utf8_unicode_ci,
                PRIMARY KEY (`id`),
                KEY `entry_id` (`entry_id`),
                FULLTEXT KEY `value` (`value`),
                FULLTEXT KEY `value_formatted` (`value_formatted`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;',
            (int) $this->id->value
        );
    }

    protected static function boolToEnumYesNo(bool $value): string
    {
        return true == $value ? 'yes' : 'no';
    }

    public function getDatabaseReadyData(): array
    {
        return [
            'field_id' => (int) $this->id->value,
            'xsltfile' => (string) $this->xsltFile->value,
            'expression' => (string) $this->expression->value,
            'formatter' => (string) $this->formatter->value,
            'override' => self::boolToEnumYesNo($this->override->value),
            'fetch_associated_counts' => self::boolToEnumYesNo($this->fetchAssociatedCounts->value),
            'hide' => self::boolToEnumYesNo($this->hide->value),
        ];
    }
}
