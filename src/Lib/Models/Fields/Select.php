<?php
namespace pointybeard\Symphony\SectionBuilder\Lib\Models\Fields;

use pointybeard\Symphony\SectionBuilder\Lib\AbstractField;
use pointybeard\Symphony\SectionBuilder\Lib\Interfaces;
use pointybeard\Symphony\SectionBuilder\Lib\Traits;
use pointybeard\PropertyBag\Lib;

class Select extends AbstractField implements Interfaces\FieldInterface, Interfaces\FieldAssociationInterface
{
    const TYPE = "select";
    const TABLE = "tbl_fields_select";

    use Traits\hasFetchAssociatedFieldTrait;

    public function associationParentSectionId(){
        return $this->fetchAssociatedField('dynamicOptions')->sectionId->value;
    }

    public function associationParentSectionFieldId(){
        return $this->fetchAssociatedField('dynamicOptions')->id->value;
    }

    public static function getFieldMappings()
    {
        return (object)array_merge((array)parent::getFieldMappings(), [
            'allow_multiple_selection' => [
                'name' => 'allowMultipleSelection',
                'flags' => self::FLAG_BOOL
            ],

            'sort_options' => [
                'name' => 'sortOptions',
                'flags' => self::FLAG_BOOL
            ],

            'static_options' => [
                'name' => 'staticOptions',
                'flags' => self::FLAG_STR
            ],

            'dynamic_options' => [
                'name' => 'dynamicOptions',
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
            'sort_options' => self::boolToEnumYesNo($this->sortOptions->value),
            'static_options' => (string)$this->staticOptions,
            'dynamic_options' => $this->associationParentSectionFieldId()
        ];
    }

    public function getEntriesDataCreateTableSyntax()
    {
        return sprintf(
            "CREATE TABLE IF NOT EXISTS `tbl_entries_data_%d` (
                `id` int(11) unsigned NOT null auto_increment,
                `entry_id` int(11) unsigned NOT null,
                `handle` varchar(255) default null,
                `value` varchar(255) default null,
                PRIMARY KEY  (`id`),
                KEY `entry_id` (`entry_id`),
                KEY `handle` (`handle`),
                KEY `value` (`value`)
              ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;",
            (int)$this->id->value
        );
    }
}
