<?php

declare(strict_types=1);

namespace pointybeard\Symphony\SectionBuilder\SectionBuilder\Models\Fields;

use pointybeard\Symphony\SectionBuilder\SectionBuilder\AbstractField;
use pointybeard\Symphony\SectionBuilder\SectionBuilder\Interfaces\FieldInterface;

class Checkbox extends AbstractField implements FieldInterface
{
    const TYPE = 'checkbox';
    const TABLE = 'tbl_fields_checkbox';

    public static function getFieldMappings(): \stdClass
    {
        return (object) array_merge((array) parent::getFieldMappings(), [
            'default_state' => [
                'name' => 'defaultState',
                'flags' => self::FLAG_STR,
            ],

            'description' => [
                'name' => 'description',
                'flags' => self::FLAG_STR,
            ],
        ]);
    }

    public function getEntriesDataCreateTableSyntax(): string
    {
        return sprintf(
            "CREATE TABLE IF NOT EXISTS `tbl_entries_data_%d` (
              `id` int(11) unsigned NOT null auto_increment,
              `entry_id` int(11) unsigned NOT null,
              `value` enum('yes','no') NOT null default '%s',
              PRIMARY KEY  (`id`),
              UNIQUE KEY `entry_id` (`entry_id`),
              KEY `value` (`value`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;",
            (int) $this->id->value,
            (
                'on' == self::stringToEnumOnOff((string) $this->defaultState)
                    ? 'yes'
                    : 'no'
            )
        );
    }

    protected static function stringToEnumOnOff(string $string): string
    {
        return 'on' == strtolower($string) ? 'on' : 'off';
    }

    public function getDatabaseReadyData(): array
    {
        return [
            'field_id' => (int) $this->id->value,
            'default_state' => self::stringToEnumOnOff((string) $this->defaultState),
            'description' => (string) $this->description,
        ];
    }
}
