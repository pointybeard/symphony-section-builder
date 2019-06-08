<?php

declare(strict_types=1);

namespace pointybeard\Symphony\SectionBuilder\Models\Fields;

use pointybeard\Symphony\SectionBuilder\AbstractField;
use pointybeard\Symphony\SectionBuilder\Interfaces\FieldInterface;

class ReverseRegex extends AbstractField implements FieldInterface
{
    const TYPE = 'reverse_regex';
    const TABLE = 'tbl_fields_reverse_regex';

    public static function getFieldMappings(): \stdClass
    {
        return (object) array_merge((array) parent::getFieldMappings(), [
            'pattern' => [
                'name' => 'pattern',
                'flags' => self::FLAG_STR,
            ],

            'unique' => [
                'name' => 'isUnique',
                'flags' => self::FLAG_BOOL,
            ],
        ]);
    }

    protected static function boolToEnumYesNo(bool $value): string
    {
        return true == $value ? 'yes' : 'no';
    }

    public function getDatabaseReadyData(): array
    {
        return [
            'field_id' => (int) $this->id->value,
            'pattern' => (string) $this->pattern,
            'unique' => self::boolToEnumYesNo($this->isUnique->value),
        ];
    }

    public function getEntriesDataCreateTableSyntax(): string
    {
        return sprintf(
            'CREATE TABLE IF NOT EXISTS `tbl_entries_data_%d` (
              `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `entry_id` int(11) unsigned NOT NULL,
              `value` varchar(36) COLLATE utf8_unicode_ci DEFAULT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `entry_id` (`entry_id`),
              KEY `value` (`value`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;',
            (int) $this->id->value
        );
    }
}
