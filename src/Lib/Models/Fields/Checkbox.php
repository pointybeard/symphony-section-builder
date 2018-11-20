<?php
namespace pointybeard\Symphony\SectionBuilder\Lib\Models\Fields;

use pointybeard\Symphony\SectionBuilder\Lib\AbstractField;
use pointybeard\Symphony\SectionBuilder\Lib\Interfaces\FieldInterface;
use pointybeard\PropertyBag\Lib;

class Checkbox extends AbstractField implements FieldInterface
{
    const TYPE = "checkbox";
    const TABLE = "tbl_fields_checkbox";

    public static function getFieldMappings()
    {
        return (object)array_merge((array)parent::getFieldMappings(), [
            'default_state' => [
                'name' => 'defaultState',
                'flags' => self::FLAG_STR
            ],

            'description' => [
                'name' => 'description',
                'flags' => self::FLAG_STR
            ],
        ]);
    }

    protected function installEntriesDataTable()
    {
        $sql = sprintf(
            "CREATE TABLE IF NOT EXISTS `tbl_entries_data_%d` (
              `id` int(11) unsigned NOT null auto_increment,
              `entry_id` int(11) unsigned NOT null,
              `value` enum('yes','no') NOT null default '%s',
              PRIMARY KEY  (`id`),
              UNIQUE KEY `entry_id` (`entry_id`),
              KEY `value` (`value`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;",
            (int)$this->id->value,
            self::stringToEnumOnOff((string)$this->defaultState)
        );
        \SymphonyPDO\Loader::instance()->exec($sql);
        return true;
    }

    protected static function stringToEnumOnOff($string)
    {
        return strtolower($string) == 'on' ? 'on' : 'off';
    }

    public function getDatabaseReadyData()
    {
        return [
            'field_id' => (int)$this->id->value,
            'default_state' => self::stringToEnumOnOff((string)$this->defaultState),
            'description' => (string)$this->description,
        ];
    }
}
