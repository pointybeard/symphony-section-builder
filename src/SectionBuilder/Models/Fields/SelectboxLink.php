<?php

declare(strict_types=1);

namespace pointybeard\Symphony\SectionBuilder\SectionBuilder\Models\Fields;

use pointybeard\Symphony\SectionBuilder\SectionBuilder\AbstractField;
use pointybeard\Symphony\SectionBuilder\SectionBuilder\Interfaces;
use pointybeard\Symphony\SectionBuilder\SectionBuilder\Traits;

class SelectboxLink extends AbstractField implements Interfaces\FieldInterface, Interfaces\FieldAssociationInterface
{
    const TYPE = 'selectbox_link';
    const TABLE = 'tbl_fields_selectbox_link';

    use Traits\hasFetchAssociatedFieldTrait;

    public static function getFieldMappings(): \stdClass
    {
        return (object) array_merge((array) parent::getFieldMappings(), [
            'allow_multiple_selection' => [
                'name' => 'allowMultipleSelection',
                'flags' => self::FLAG_BOOL,
            ],

            'hide_when_prepopulated' => [
                'name' => 'hideWhenPrepopulated',
                'flags' => self::FLAG_BOOL,
            ],

            'limit' => [
                'name' => 'limit',
                'flags' => self::FLAG_INT,
            ],

            'related_field_id' => [
                'name' => 'relatedFieldId',
                'flags' => self::FLAG_FIELD,
            ],
        ]);
    }

    public function hasAssociations(): bool
    {
        return
            $this instanceof Interfaces\FieldAssociationInterface
            && null !== $this->relatedFieldId->value
        ;
    }

    public function associationParentSectionId(): ?int
    {
        return null !== $this->relatedFieldId->value
            ? (int) $this->fetchAssociatedField('relatedFieldId')->sectionId->value
            : null
        ;
    }

    public function associationParentSectionFieldId(): ?int
    {
        return null !== $this->relatedFieldId->value
            ? (int) $this->fetchAssociatedField('relatedFieldId')->id->value
            : null
        ;
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
            'hide_when_prepopulated' => self::boolToEnumYesNo($this->hideWhenPrepopulated->value),
            'limit' => (int) $this->limit->value,
            'related_field_id' => $this->associationParentSectionFieldId(),
        ];
    }

    public function getEntriesDataCreateTableSyntax(): string
    {
        return sprintf(
            'CREATE TABLE IF NOT EXISTS `tbl_entries_data_%d` (
                `id` int(11) unsigned NOT NULL auto_increment,
                `entry_id` int(11) unsigned NOT NULL,
                `relation_id` int(11) unsigned DEFAULT NULL,
                PRIMARY KEY (`id`),
                KEY `entry_id` (`entry_id`),
                KEY `relation_id` (`relation_id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;',
            (int) $this->id->value
        );
    }
}
