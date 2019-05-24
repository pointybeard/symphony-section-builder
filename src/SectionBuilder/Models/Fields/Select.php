<?php

declare(strict_types=1);

namespace pointybeard\Symphony\SectionBuilder\SectionBuilder\Models\Fields;

use pointybeard\Symphony\SectionBuilder\SectionBuilder\AbstractField;
use pointybeard\Symphony\SectionBuilder\SectionBuilder\Interfaces;
use pointybeard\Symphony\SectionBuilder\SectionBuilder\Traits;
use pointybeard\PropertyBag\Lib;

class Select extends AbstractField implements Interfaces\FieldInterface, Interfaces\FieldAssociationInterface
{
    const TYPE = 'select';
    const TABLE = 'tbl_fields_select';

    use Traits\hasFetchAssociatedFieldTrait;

    public function hasAssociations(): bool
    {
        return
            $this instanceof Interfaces\FieldAssociationInterface
            && ($this->dynamicOptions instanceof Lib\Property)
            && null !== $this->dynamicOptions->value
        ;
    }

    public function associationParentSectionId(): ?int
    {
        return ($this->dynamicOptions instanceof Lib\Property) && null !== $this->dynamicOptions->value
            ? (int) $this->fetchAssociatedField('dynamicOptions')->sectionId->value
            : null
        ;
    }

    public function associationParentSectionFieldId(): ?int
    {
        return ($this->dynamicOptions instanceof Lib\Property) && null !== $this->dynamicOptions->value
            ? (int) $this->fetchAssociatedField('dynamicOptions')->id->value
            : null
        ;
    }

    public static function getFieldMappings(): \stdClass
    {
        return (object) array_merge((array) parent::getFieldMappings(), [
            'allow_multiple_selection' => [
                'name' => 'allowMultipleSelection',
                'flags' => self::FLAG_BOOL,
            ],

            'sort_options' => [
                'name' => 'sortOptions',
                'flags' => self::FLAG_BOOL,
            ],

            'static_options' => [
                'name' => 'staticOptions',
                'flags' => self::FLAG_STR,
            ],

            'dynamic_options' => [
                'name' => 'dynamicOptions',
                'flags' => self::FLAG_FIELD | self::FLAG_NULL,
            ],
        ]);
    }

    protected static function boolToEnumYesNo(bool $value): string
    {
        return true == $value ? 'yes' : 'no';
    }

    public function getDatabaseReadyData(): array
    {
        return [
            'field_id' => (int) $this->id->value,
            'allow_multiple_selection' => self::boolToEnumYesNo($this->allowMultipleSelection->value),
            'sort_options' => self::boolToEnumYesNo($this->sortOptions->value),
            'static_options' => (string) $this->staticOptions,
            'dynamic_options' => $this->associationParentSectionFieldId(),
        ];
    }

    public function getEntriesDataCreateTableSyntax(): string
    {
        return sprintf(
            'CREATE TABLE IF NOT EXISTS `tbl_entries_data_%d` (
                `id` int(11) unsigned NOT null auto_increment,
                `entry_id` int(11) unsigned NOT null,
                `handle` varchar(255) default null,
                `value` varchar(255) default null,
                PRIMARY KEY  (`id`),
                KEY `entry_id` (`entry_id`),
                KEY `handle` (`handle`),
                KEY `value` (`value`)
              ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;',
            (int) $this->id->value
        );
    }
}
