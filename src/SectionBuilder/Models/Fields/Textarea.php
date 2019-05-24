<?php

declare(strict_types=1);

namespace pointybeard\Symphony\SectionBuilder\SectionBuilder\Models\Fields;

use pointybeard\Symphony\SectionBuilder\SectionBuilder\AbstractField;
use pointybeard\Symphony\SectionBuilder\SectionBuilder\Interfaces\FieldInterface;

class Textarea extends AbstractField implements FieldInterface
{
    const TYPE = 'textarea';
    const TABLE = 'tbl_fields_textarea';

    public static function getFieldMappings(): \stdClass
    {
        return (object) array_merge((array) parent::getFieldMappings(), [
            'size' => [
                'name' => 'size',
                'flags' => self::FLAG_INT,
            ],

            'formatter' => [
                'name' => 'formatter',
                'flags' => self::FLAG_STR,
            ],
        ]);
    }

    public function getEntriesDataCreateTableSyntax(): string
    {
        return sprintf(
            'CREATE TABLE IF NOT EXISTS `tbl_entries_data_%d` (
                `id` int(11) unsigned NOT null auto_increment,
                `entry_id` int(11) unsigned NOT null,
                `value` MEDIUMTEXT,
                `value_formatted` MEDIUMTEXT,
                PRIMARY KEY  (`id`),
                UNIQUE KEY `entry_id` (`entry_id`),
                FULLTEXT KEY `value` (`value`)
              ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;',
            (int) $this->id->value
        );
    }

    public function getDatabaseReadyData(): array
    {
        return [
            'field_id' => (int) $this->id->value,
            'size' => (int) $this->size->value,
            'formatter' => (string) $this->formatter,
        ];
    }
}
