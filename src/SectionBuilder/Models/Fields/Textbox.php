<?php

declare(strict_types=1);

namespace pointybeard\Symphony\SectionBuilder\Models\Fields;

use pointybeard\Symphony\SectionBuilder\AbstractField;
use pointybeard\Symphony\SectionBuilder\Interfaces\FieldInterface;

class Textbox extends AbstractField implements FieldInterface
{
    const TYPE = 'textbox';
    const TABLE = 'sym_fields_textbox';

    public static function getFieldMappings(): \stdClass
    {
        return (object) array_merge((array) parent::getFieldMappings(), [
            'column_length' => [
                'name' => 'columnLength',
                'flags' => self::FLAG_INT | self::FLAG_NULL,
            ],

            'text_size' => [
                'name' => 'textSize',
                'flags' => self::FLAG_STR | self::FLAG_NULL,
            ],

            'text_formatter' => [
                'name' => 'textFormatter',
                'flags' => self::FLAG_STR | self::FLAG_NULL,
            ],

            'text_validator' => [
                'name' => 'textValidator',
                'flags' => self::FLAG_STR | self::FLAG_NULL,
            ],

            'text_length' => [
                'name' => 'textLength',
                'flags' => self::FLAG_INT | self::FLAG_NULL,
            ],

            'text_cdata' => [
                'name' => 'textCDATA',
                'flags' => self::FLAG_BOOL | self::FLAG_NULL,
            ],

            'text_handle' => [
                'name' => 'textHandle',
                'flags' => self::FLAG_BOOL | self::FLAG_NULL,
            ],

            'handle_unique' => [
                'name' => 'handleUnique',
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
            'column_length' => $this->columnLength->value == NULL ? NULL : (int) $this->columnLength->value,
            'text_size' => $this->textSize->value,
            'text_formatter' => $this->textFormatter->value,
            'text_validator' => $this->textValidator->value,
            'text_length' => $this->textLength->value == NULL ? NULL : (int) $this->textLength->value,
            'text_cdata' => self::boolToEnumYesNo($this->textCDATA->value),
            'text_handle' => self::boolToEnumYesNo($this->textHandle->value),
            'handle_unique' => self::boolToEnumYesNo($this->handleUnique->value),
        ];
    }
}
