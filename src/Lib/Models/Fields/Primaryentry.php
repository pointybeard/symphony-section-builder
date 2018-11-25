<?php
namespace pointybeard\Symphony\SectionBuilder\Lib\Models\Fields;

use pointybeard\Symphony\SectionBuilder\Lib\AbstractField;
use pointybeard\Symphony\SectionBuilder\Lib\Interfaces\FieldInterface;
use pointybeard\PropertyBag\Lib;

class Primaryentry extends AbstractField implements FieldInterface
{
    const TYPE = "primaryentry";
    const TABLE = "tbl_fields_primaryentry";

    public static function getFieldMappings()
    {
        return (object)array_merge((array)parent::getFieldMappings(), [
            'default_state' => [
                'name' => 'defaultState',
                'flags' => self::FLAG_STR
            ],

            'auto_toggle' => [
                'name' => 'autoToggle',
                'flags' => self::FLAG_BOOL
            ],

        ]);
    }

    public function getEntriesDataCreateTableSyntax()
    {
        return sprintf(
            "CREATE TABLE IF NOT EXISTS `tbl_entries_data_%d` (
              `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `entry_id` int(11) unsigned NOT NULL,
              `value` enum('yes','no') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'no',
              PRIMARY KEY (`id`),
              UNIQUE KEY `entry_id` (`entry_id`),
              KEY `value` (`value`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;",
            (int)$this->id->value
        );
    }

    protected static function stringToEnumOnOff($string)
    {
        return strtolower($string) == 'on' ? 'on' : 'off';
    }

    protected static function boolToEnumYesNo($value)
    {
        return $value == true ? 'yes' : 'no';
    }

    public function getDatabaseReadyData()
    {
        return [
            'field_id' => (int)$this->id->value,
            'default_state' => self::stringToEnumOnOff((string)$this->defaultState),
            'auto_toggle' => self::boolToEnumYesNo($this->autoToggle->value),
        ];
    }
}
