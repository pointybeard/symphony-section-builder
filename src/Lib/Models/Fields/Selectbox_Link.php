<?php
namespace pointybeard\Symphony\SectionBuilder\Lib\Models\Fields;

use pointybeard\Symphony\SectionBuilder\Lib\AbstractField;
use pointybeard\Symphony\SectionBuilder\Lib\Interfaces\FieldInterface;
use pointybeard\PropertyBag\Lib;

class Selectbox_Link extends AbstractField implements FieldInterface
{
    const TYPE = "select";
    const TABLE = "tbl_fields_selectbox_link";

    public static function getFieldMappings()
    {
        return (object)array_merge((array)parent::getFieldMappings(), [
            'allow_multiple_selection' => [
                'name' => 'allowMultipleSelection',
                'flags' => self::FLAG_BOOL
            ],

            'hide_when_prepopulated' => [
                'name' => 'hideWhenPrepopulated',
                'flags' => self::FLAG_BOOL
            ],

            'limit' => [
                'name' => 'limit',
                'flags' => self::FLAG_INT
            ],

            'related_field_id' => [
                'name' => 'relatedFieldId',
                'flags' => self::FLAG_FIELD
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
            'allow_multiple_selection' => self::boolToEnumYesNo($this->allowMultipleSelection->value),
            'hide_when_prepopulated' => self::boolToEnumYesNo($this->hideWhenPrepopulated->value),
            'limit' => (int)$this->limit->value,
            'related_field_id' =>
                ($this->relatedFieldId->value instanceof AbstractField
                    ? $this->relatedFieldId->value->id->value
                    : $this->relatedFieldId->value)

        ];
    }

    protected function installEntriesDataTable()
    {
        $sql = sprintf(
            "CREATE TABLE IF NOT EXISTS `tbl_entries_data_%d` (
                `id` int(11) unsigned NOT NULL auto_increment,
                `entry_id` int(11) unsigned NOT NULL,
                `relation_id` int(11) unsigned DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `entry_id` (`entry_id`),
                KEY `relation_id` (`relation_id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;",
            (int)$this->id->value
        );
        \Symphony::database()->exec($sql);
        return true;
    }
}
