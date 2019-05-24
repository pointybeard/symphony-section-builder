<?php

declare(strict_types=1);

namespace pointybeard\Symphony\SectionBuilder\SectionBuilder\Models\Fields;

use pointybeard\Symphony\SectionBuilder\SectionBuilder\AbstractField;
use pointybeard\Symphony\SectionBuilder\SectionBuilder\Interfaces\FieldInterface;

class Upload extends AbstractField implements FieldInterface
{
    const TYPE = 'upload';
    const TABLE = 'tbl_fields_upload';

    public static function getFieldMappings(): \stdClass
    {
        return (object) array_merge((array) parent::getFieldMappings(), [
            'destination' => [
                'name' => 'destination',
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
            'destination' => (string) $this->destination,
            'validator' => (string) $this->validator,
        ];
    }

    public function getEntriesDataCreateTableSyntax(): string
    {
        return sprintf(
            'CREATE TABLE IF NOT EXISTS `tbl_entries_data_%d` (
                `id` int(11) unsigned NOT null auto_increment,
                `entry_id` int(11) unsigned NOT null,
                `file` varchar(255) default null,
                `size` int(11) unsigned null,
                `mimetype` varchar(100) default null,
                `meta` varchar(255) default null,
                PRIMARY KEY  (`id`),
                UNIQUE KEY `entry_id` (`entry_id`),
                KEY `file` (`file`),
                KEY `mimetype` (`mimetype`)
              ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;',
            (int) $this->id->value
        );
    }
}