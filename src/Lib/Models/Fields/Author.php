<?php
namespace pointybeard\Symphony\SectionBuilder\Lib\Models\Fields;

use pointybeard\Symphony\SectionBuilder\Lib\AbstractField;
use pointybeard\Symphony\SectionBuilder\Lib\Interfaces\FieldInterface;
use pointybeard\PropertyBag\Lib;

class Author extends AbstractField implements FieldInterface
{
    const TYPE = "author";
    const TABLE = "tbl_fields_author";

    public static function getFieldMappings()
    {
        return (object)array_merge((array)parent::getFieldMappings(), [
            'allow_multiple_selection' => [
                'name' => 'allowMultipleSelection',
                'flags' => self::FLAG_BOOL,
            ],

            'default_to_current_user' => [
                'name' => 'defaultToCurrentUser',
                'flags' => self::FLAG_BOOL
            ],

            'author_types' => [
                'name' => 'authorTypes',
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
              `author_id` int(11) unsigned null,
              PRIMARY KEY  (`id`),
              UNIQUE KEY `author` (`entry_id`, `author_id`),
              KEY `author_id` (`author_id`)
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
            'author_types' => (string)$this->authorTypes,
            'allow_multiple_selection' => self::boolToEnumYesNo($this->allowMultipleSelection->value),
            'default_to_current_user' => self::boolToEnumYesNo($this->defaultToCurrentUser->value),
        ];
    }
}
