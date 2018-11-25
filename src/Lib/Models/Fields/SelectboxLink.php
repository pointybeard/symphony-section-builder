<?php
namespace pointybeard\Symphony\SectionBuilder\Lib\Models\Fields;

use pointybeard\Symphony\SectionBuilder\Lib\AbstractField;
use pointybeard\Symphony\SectionBuilder\Lib\Interfaces;
use pointybeard\Symphony\SectionBuilder\Lib\Traits;
use pointybeard\PropertyBag\Lib;

class SelectboxLink extends AbstractField implements Interfaces\FieldInterface, Interfaces\FieldAssociationInterface
{
    const TYPE = "selectbox_link";
    const TABLE = "tbl_fields_selectbox_link";

    use Traits\hasFetchAssociatedFieldTrait;

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

    public function hasAssociations() {
        return (
            $this instanceof Interfaces\FieldAssociationInterface
            && !is_null($this->relatedFieldId->value)
        );
    }

    public function associationParentSectionId(){
        return !is_null($this->relatedFieldId->value)
            ? $this->fetchAssociatedField('relatedFieldId')->sectionId->value
            : null
        ;
    }

    public function associationParentSectionFieldId(){
        return !is_null($this->relatedFieldId->value)
            ? $this->fetchAssociatedField('relatedFieldId')->id->value
            : null
        ;
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
            'related_field_id' => $this->associationParentSectionFieldId()
        ];
    }

    public function getEntriesDataCreateTableSyntax()
    {
        return sprintf(
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
    }
}
