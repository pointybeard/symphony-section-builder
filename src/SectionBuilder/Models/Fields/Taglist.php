<?php

declare(strict_types=1);

namespace pointybeard\Symphony\SectionBuilder\SectionBuilder\Models\Fields;

use pointybeard\Symphony\SectionBuilder\SectionBuilder\AbstractField;
use pointybeard\Symphony\SectionBuilder\SectionBuilder\Interfaces\FieldInterface;

class Taglist extends AbstractField implements FieldInterface
{
    const TYPE = 'taglist';
    const TABLE = 'tbl_fields_taglist';

    public static function getFieldMappings(): \stdClass
    {
        return (object) array_merge((array) parent::getFieldMappings(), [
            'pre_populate_source' => [
                'name' => 'prePopulateSource',
                'flags' => self::FLAG_STR,
            ],

            'validator' => [
                'name' => 'validator',
                'flags' => self::FLAG_STR,
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
            'pre_populate_source' => (string) $this->prePopulateSource,
            'validator' => (string) $this->validator,
        ];
    }

    public function getEntriesDataCreateTableSyntax(): string
    {
        return sprintf(
            'CREATE TABLE IF NOT EXISTS `tbl_entries_data_%d` (
                `id` int(11) unsigned NOT null auto_increment,
                `entry_id` int(11) unsigned NOT null,
                `handle` varchar(255) default null,
                `value` varchar(255) default null,
                PRIMARY KEY  (`id`),
                KEY `entry_id` (`entry_id`),
                KEY `handle` (`handle`),
                KEY `value` (`value`)
              ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;',
            (int) $this->id->value
        );
    }
}
