<?php
namespace pointybeard\Symphony\SectionBuilder\Lib\Models\Fields;

use pointybeard\Symphony\SectionBuilder\Lib\AbstractField;
use pointybeard\Symphony\SectionBuilder\Lib\Interfaces\FieldInterface;
use pointybeard\PropertyBag\Lib;

class Input extends AbstractField implements FieldInterface
{
    const TYPE = "input";
    const TABLE = "tbl_fields_input";

    public static function getFieldMappings()
    {
        return (object)array_merge((array)parent::getFieldMappings(), [
            'validator' => [
                'name' => 'validator',
                'flags' => self::FLAG_STR
            ],
        ]);
    }

    public function getDatabaseReadyData()
    {
        return [
            'field_id' => (int)$this->id->value,
            'validator' => (string)$this->validator,
        ];
    }

    public function getEntriesDataCreateTableSyntax()
    {
        return sprintf(
            "CREATE TABLE IF NOT EXISTS `tbl_entries_data_%d` (
              `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `entry_id` int(11) unsigned NOT NULL,
              `handle` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
              `value` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
              PRIMARY KEY (`id`),
              UNIQUE KEY `entry_id` (`entry_id`),
              KEY `handle` (`handle`),
              KEY `value` (`value`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;",
            (int)$this->id->value
        );
    }
}
