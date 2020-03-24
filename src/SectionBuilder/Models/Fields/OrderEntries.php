<?php

declare(strict_types=1);

namespace pointybeard\Symphony\SectionBuilder\Models\Fields;

use pointybeard\Symphony\SectionBuilder\AbstractField;
use pointybeard\Symphony\SectionBuilder\Interfaces\FieldInterface;

class OrderEntries extends AbstractField implements FieldInterface
{
    const TYPE = 'order_entries';
    const TABLE = 'sym_fields_order_entries';

    public static function getFieldMappings(): \stdClass
    {
        return (object) array_merge((array) parent::getFieldMappings(), [
            'filtered_fields' => [
                'name' => 'filteredFields',
                'flags' => self::FLAG_STR | self::FLAG_NULL,
            ],

            'disable_pagination' => [
                'name' => 'disablePagination',
                'flags' => self::FLAG_BOOL,
            ],

            'force_sort' => [
                'name' => 'forceSort',
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
                `handle` varchar(1024) COLLATE utf8_unicode_ci DEFAULT NULL,
                `value` text COLLATE utf8_unicode_ci,
                `value_formatted` text COLLATE utf8_unicode_ci,
                `word_count` int(11) unsigned DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `entry_id` (`entry_id`),
                KEY `handle` (`handle`(333)),
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
            'filtered_fields' => (string) $this->filteredFields->value,
            'disable_pagination' => self::boolToEnumYesNo($this->disablePagination->value),
            'force_sort' => self::boolToEnumYesNo($this->forceSort->value),
            'hide' => self::boolToEnumYesNo($this->hide->value),
        ];
    }
}
