<?php

declare(strict_types=1);

/*
 * This file is part of the "Symphony CMS: Section Builder" repository.
 *
 * Copyright 2018-2020 Alannah Kearney <hi@alannahkearney.com>
 *
 * For the full copyright and license information, please view the LICENCE
 * file that was distributed with this source code.
 */

namespace pointybeard\Symphony\SectionBuilder\Models;

use pointybeard\PropertyBag\Lib;
use pointybeard\Symphony\SectionBuilder\AbstractField;
use pointybeard\Symphony\SectionBuilder\AbstractTableModel;
use pointybeard\Symphony\SectionBuilder\Exceptions;
use SymphonyPDO;
use SymphonyPDO\Lib\ResultIterator;

class Section extends AbstractTableModel
{
    protected $fields = [];
    protected $isFieldsInitialised = false;
    const TABLE = 'tbl_sections';

    public function __toArray(): array
    {
        $mapping = self::getFieldMappings();
        $data = $this->getDatabaseReadyData();

        $output = [
            'fields' => [],
            'associations' => [],
        ];

        foreach ($mapping as $name => $properties) {
            // Add this to the start of the array.
            $output = [
                $properties['name'] => $this->{$properties['name']}->value,
            ] + $output;
        }

        foreach ($this->fields() as $f) {
            $output['fields'][] = $f->__toArray();
        }

        foreach ($this->associations() as $a) {
            $output['associations'][] = $a->__toArray();
        }

        return $output;
    }

    public static function getFieldMappings(): \stdClass
    {
        return (object) [
            'id' => [
                'name' => 'id',
                'flags' => self::FLAG_INT | self::FLAG_IMMUTABLE,
            ],

            'name' => [
                'name' => 'name',
                'flags' => self::FLAG_STR,
            ],

            'handle' => [
                'name' => 'handle',
                'flags' => self::FLAG_STR,
            ],

            'sortorder' => [
                'name' => 'sortOrder',
                'flags' => self::FLAG_INT,
            ],

            'hidden' => [
                'name' => 'hideFromBackendNavigation',
                'flags' => self::FLAG_BOOL,
                'default' => false,
            ],

            'filter' => [
                'name' => 'allowFiltering',
                'flags' => self::FLAG_BOOL,
                'default' => true,
            ],

            'navigation_group' => [
                'name' => 'navigationGroup',
                'flags' => self::FLAG_STR,
                'default' => 'Content',
            ],

            'author_id' => [
                'name' => 'authorId',
                'flags' => self::FLAG_INT,
                'default' => 1,
            ],

            'modification_author_id' => [
                'name' => 'modificationAuthorId',
                'flags' => self::FLAG_INT,
                'default' => 1,
            ],

            'creation_date' => [
                'name' => 'dateCreatedAt',
                'flags' => self::FLAG_DATE,
                'default' => date('c'),
            ],

            'creation_date_gmt' => [
                'name' => 'dateCreatedAtGMT',
                'flags' => self::FLAG_DATE,
                'default' => gmdate('c'),
            ],

            'modification_date' => [
                'name' => 'dateModifiedAt',
                'flags' => self::FLAG_DATE,
                'default' => date('c'),
            ],

            'modification_date_gmt' => [
                'name' => 'dateModifiedAtGMT',
                'flags' => self::FLAG_DATE,
                'default' => gmdate('c'),
            ],
        ];
    }

    public static function loadFromName(string $name): ?self
    {
        $db = SymphonyPDO\Loader::instance();

        $query = $db->prepare(sprintf('SELECT * FROM `%s` WHERE `name` = :name LIMIT 1', self::TABLE));
        $query->bindParam(':name', $name, \PDO::PARAM_STR);
        $result = $query->execute();

        $section = (new ResultIterator(
            self::class,
            $query
        ))->current();

        return ($section instanceof self) ? $section : null;
    }

    public static function loadFromHandle(string $handle): ?self
    {
        $db = SymphonyPDO\Loader::instance();

        $query = $db->prepare(sprintf('SELECT * FROM `%s` WHERE `handle` = :handle LIMIT 1', self::TABLE));
        $query->bindParam(':handle', $handle, \PDO::PARAM_STR);
        $result = $query->execute();

        $section = (new ResultIterator(
            self::class,
            $query
        ))->current();

        return ($section instanceof self) ? $section : null;
    }

    protected function findNextSortOrderValue(): int
    {
        $query = SymphonyPDO\Loader::instance()->prepare('SELECT MAX(`sortorder`) + 1 as `value` FROM '.self::TABLE);
        $result = $query->execute();

        return max(1, (int) $query->fetchColumn());
    }

    public function getDatabaseReadyData(): array
    {
        return [
            'id' => $this->id->value,
            'name' => (string) $this->name,
            'handle' => (string) $this->handle,
            'sortorder' => (
                null == $this->sortOrder->value
                    ? self::findNextSortOrderValue()
                    : $this->sortOrder->value
            ),
            'hidden' => (
                $this->hideFromBackendNavigation->value
                    ? 'yes'
                    : 'no'
            ),
            'filter' => (
                $this->allowFiltering->value
                    ? 'yes'
                    : 'no'
            ),
            'navigation_group' => (string) $this->navigationGroup,
            'author_id' => $this->authorId->value,
            'modification_author_id' => $this->modificationAuthorId->value,
            'creation_date' => date('Y-m-d H:i:s', strtotime((string) $this->dateCreatedAt)),
            'creation_date_gmt' => date('Y-m-d H:i:s', strtotime((string) $this->dateCreatedAtGMT)),
            'modification_date' => date('Y-m-d H:i:s', strtotime((string) $this->dateModifiedAt)),
            'modification_date_gmt' => date('Y-m-d H:i:s', strtotime((string) $this->dateModifiedAtGMT)),
        ];
    }

    public function commit(): AbstractTableModel
    {
        $this->initiliseExistingFields();

        $section = &$this;

        SymphonyPDO\Loader::instance()->doInTransaction(
            function (SymphonyPDO\Lib\Database $db) use ($section) {
                $id = $db->insertUpdate(
                    $section->getDatabaseReadyData(),
                    [
                        'name',
                        'handle',
                        'sortorder',
                        'hidden',
                        'filter',
                        'navigation_group',
                        'modification_date',
                        'modification_date_gmt',
                    ],
                    self::TABLE
                );

                if (!($section->id instanceof Lib\ImmutableProperty) && null == $section->id->value) {
                    $section->id((int) $id);
                }

                //Remove all existing associations for this section (childSectionId)
                foreach ($section->associations() as $a) {
                    $a->delete();
                }

                // Save each field
                for ($ii = 0; $ii < count($section->fields); ++$ii) {
                    $section->fields[$ii]
                        ->sectionId((int) $section->id->value)
                        ->commit()
                    ;

                    // Ask the field if it needs to update any associations
                    if ($section->fields[$ii]->hasAssociations()) {
                        (new SectionAssociation())
                            ->parentSectionId($section->fields[$ii]->associationParentSectionId())
                            ->parentSectionFieldId($section->fields[$ii]->associationParentSectionFieldId())
                            ->childSectionId($section->id->value)
                            ->childSectionFieldId($section->fields[$ii]->id->value)
                            ->commit()
                        ;
                    }
                }

                return true;
            }
        );

        // Since installEntriesDataTable() would not have been called
        // on each field (because we were inside a transaction when we called
        // the field commit() method), we'll need to do that ourself. The only
        // issue here is that if the query fails, we cannot rollback any of the
        // stuff we did earlier (that transaction is long since closed).
        foreach ($this->fields as $f) {
            $f->installEntriesDataTable();
        }

        return $this;
    }

    public function associations(): SymphonyPDO\Lib\ResultIterator
    {
        return SectionAssociation::fetchByChildSectionId((int) $this->id->value);
    }

    protected function initiliseExistingFields(): void
    {
        $sectionId = (int) $this->id->value;

        // Only try to initialise fields if this section has an id (i.e. it's
        // an existing section, not a new one). We also cannot initialise fields
        // if there are already fields added.
        if (null !== $sectionId && true != $this->isFieldsInitialised) {
            $this->isFieldsInitialised = true;

            $db = SymphonyPDO\Loader::instance();
            $query = $db->prepare(sprintf('SELECT `id` FROM `%s` WHERE `parent_section` = :sectionId', AbstractField::TABLE));
            $query->bindParam(':sectionId', $sectionId, \PDO::PARAM_INT);
            $result = $query->execute();

            while (false !== $fieldId = $query->fetchColumn()) {
                $this->addField(AbstractField::loadFromId((int) $fieldId));
            }
        }
    }

    public function fields(): array
    {
        $this->initiliseExistingFields();

        return $this->fields;
    }

    public function &findFieldByElementName($elementName): AbstractField
    {
        // Make sure existing fields area already initilised
        $this->initiliseExistingFields();
        for ($ii = 0; $ii < count($this->fields); ++$ii) {
            if ((string) $this->fields[$ii]->elementName == $elementName) {
                return $this->fields[$ii];
            }
        }

        throw new Exceptions\NoSuchFieldException("Unable to locate field with element name '{$elementName}' in section.");
    }

    public function &findFieldById($id): AbstractField
    {
        // Make sure existing fields area already initilised
        $this->initiliseExistingFields();
        for ($ii = 0; $ii < count($this->fields); ++$ii) {
            if ((int) $this->fields[$ii]->id->value == $id) {
                return $this->fields[$ii];
            }
        }

        throw new Exceptions\NoSuchFieldException("Unable to locate field with id '{$id}' in section.");
    }

    public function addField(AbstractField $field): self
    {
        // Make sure existing fields area already initilised
        $this->initiliseExistingFields();

        // TODO: Check if field already exists etc..
        foreach ($this->fields as $f) {
            if ($f->elementName->value == $field->elementName) {
                throw new Exceptions\CannotAddFieldToSectionException("Field with element name '{$field->elementName}' already exists in this section.");
            }
        }

        $this->fields[] = $field;

        return $this;
    }
}
