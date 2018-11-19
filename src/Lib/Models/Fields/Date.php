<?php
namespace pointybeard\Symphony\SectionBuilder\Lib\Models\Fields;

use pointybeard\Symphony\SectionBuilder\Lib\AbstractField;
use pointybeard\Symphony\SectionBuilder\Lib\Interfaces\FieldInterface;
use pointybeard\PropertyBag\Lib;

class Date extends AbstractField implements FieldInterface
{
    const TYPE = "date";
    const TABLE = "tbl_fields_date";

    public static function getFieldMappings()
    {
        return (object)array_merge((array)parent::getFieldMappings(), [
            'pre_populate' => [
                'name' => 'prePopulate',
                'flags' => self::FLAG_STR,
            ],

            'calendar' => [
                'name' => 'calendar',
                'flags' => self::FLAG_BOOL
            ],

            'time' => [
                'name' => 'time',
                'flags' => self::FLAG_BOOL
            ],

        ]);
    }

    protected function installEntriesDataTable()
    {
        $sql = sprintf(
            "CREATE TABLE IF NOT EXISTS `tbl_entries_data_%d` (
                `id` int(11) unsigned NOT null auto_increment,
                `entry_id` int(11) unsigned NOT null,
                `value` varchar(80) default null,
                `date` DATETIME default null,
                PRIMARY KEY  (`id`),
                UNIQUE KEY `entry_id` (`entry_id`),
                KEY `value` (`value`),
                KEY `date` (`date`)
              ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;",
            (int)$this->id->value
        );
        \Symphony::database()->exec($sql);
        return true;
    }

    protected static function boolToEnumYesNo($value)
    {
        return $value == true ? 'yes' : 'no';
    }

    public function getDatabaseReadyData()
    {
        return [
            'field_id' => (int)$this->id->value,
            'pre_populate' => (string)$this->prePopulate,
            'calendar' => self::boolToEnumYesNo($this->calendar->value),
            'time' => self::boolToEnumYesNo($this->time->value),
        ];
    }
}
