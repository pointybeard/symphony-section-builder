<?php
namespace pointybeard\Symphony\SectionBuilder\Lib\Models\Fields;

use pointybeard\Symphony\SectionBuilder\Lib\AbstractField;
use pointybeard\Symphony\SectionBuilder\Lib\Interfaces\FieldInterface;
use pointybeard\PropertyBag\Lib;

class Upload extends AbstractField implements FieldInterface
{
    const TYPE = "upload";
    const TABLE = "tbl_fields_upload";

    public static function getFieldMappings()
    {
        return (object)array_merge((array)parent::getFieldMappings(), [
            'destination' => [
                'name' => 'destination',
                'flags' => self::FLAG_STR
            ],

            'validator' => [
                'name' => 'validator',
                'flags' => self::FLAG_STR
            ],

        ]);
    }

    protected static function boolToEnumYesNo($value)
    {
        return $value == true ? 'yes' : 'no';
    }

    public function getDatabaseReadyData()
    {
        return [
            'field_id' => (int)$this->id->value,
            'destination' => (string)$this->destination,
            'validator' => (string)$this->validator,
        ];
    }

    protected function installEntriesDataTable()
    {
        $sql = sprintf(
            "CREATE TABLE IF NOT EXISTS `tbl_entries_data_%d` (
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
              ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;",
            (int)$this->id->value
        );
        \SymphonyPDO\Loader::instance()->exec($sql);
        return true;
    }
}
