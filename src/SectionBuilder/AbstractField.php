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

namespace pointybeard\Symphony\SectionBuilder;

use pointybeard\Helpers\Functions\Flags;
use SymphonyPDO;
use SymphonyPDO\Lib\ResultIterator;

abstract class AbstractField extends AbstractTableModel
{
    const PLACEMENT_MAIN_CONTENT = 'main';
    const PLACEMENT_SIDEBAR = 'sidebar';
    const TABLE = 'tbl_fields';

    public function __construct()
    {
        // Set up field so we don't get errors later
        foreach (static::getFieldMappings() as $m) {
            $this->{$m['name']}(null);
        }
    }

    public function hasAssociations(): bool
    {
        return $this instanceof Interfaces\FieldAssociationInterface;
    }

    protected static function replaceTablePrefix(string $sql): string
    {
        $tablePrefix = SymphonyPDO\Loader::getCredentials()->tbl_prefix;
        if ('tbl_' !== $tablePrefix) {
            $sql = preg_replace('/tbl_(\S+?)([\s\.,]|$)/', $tablePrefix.'\\1\\2', $sql);
        }

        return $sql;
    }

    public function hasEntriesDataTable(): bool
    {
        $sql = sprintf("SHOW TABLES LIKE 'tbl_entries_data_%d'", (int) $this->id->value);
        $result = SymphonyPDO\Loader::instance()->query(static::replaceTablePrefix($sql));

        return false !== $result->fetchObject();
    }

    public function installEntriesDataTable(bool $dropExisting = false): bool
    {
        // Check to see if the table already exists
        if (true == $this->hasEntriesDataTable() && false == $dropExisting) {
            return true;
        }

        $sql = static::replaceTablePrefix(static::getEntriesDataCreateTableSyntax());

        if (true == $dropExisting) {
            SymphonyPDO\Loader::instance()->exec(static::replaceTablePrefix(
                sprintf('DROP TABLE IF EXISTS `tbl_entries_data_%d`;', (int) $this->id->value)
            ));
        }

        return false !== SymphonyPDO\Loader::instance()->exec($sql);
    }

    public function __toArray(): array
    {
        $mapping = static::getFieldMappings();
        $baseData = self::getDatabaseReadyData();
        $customData = static::getDatabaseReadyData();

        $output = [
            'custom' => [],
        ];
        foreach ($mapping as $name => $properties) {
            if (isset($baseData[$name])) {
                // Add this to the start of the array.
                $output = [
                    $properties['name'] => $this->{$properties['name']}->value,
                ] + $output;
            } elseif (isset($customData[$name])) {
                if (Flags\is_flag_set($properties['flags'], self::FLAG_FIELD)) {
                    $associatedField = $this->fetchAssociatedField($properties['name']);
                    $output['custom'][$properties['name']] = [
                        'section' => (string) $associatedField->section()->handle,
                        'field' => (string) $associatedField->elementName,
                    ];
                } else {
                    $output['custom'][$properties['name']] = $this->{$properties['name']}->value;
                }
            }
        }

        return $output;
    }

    public function section(): ?Models\Section
    {
        if (null == $this->sectionId->value) {
            return null;
        }

        return Models\Section::loadFromId($this->sectionId->value);
    }

    public static function getFieldMappings(): \stdClass
    {
        return (object) [
            'id' => [
                'name' => 'id',
                'flags' => self::FLAG_INT | self::FLAG_IMMUTABLE,
            ],

            'type' => [
                'name' => 'type',
                'flags' => self::FLAG_STR,
            ],

            'label' => [
                'name' => 'label',
                'flags' => self::FLAG_STR,
            ],

            'element_name' => [
                'name' => 'elementName',
                'flags' => self::FLAG_STR,
            ],

            'parent_section' => [
                'name' => 'sectionId',
                'flags' => self::FLAG_INT,
            ],

            'sortorder' => [
                'name' => 'sortOrder',
                'flags' => self::FLAG_INT,
            ],

            'location' => [
                'name' => 'location',
                'flags' => self::FLAG_STR,
            ],

            'show_column' => [
                'name' => 'showColumn',
                'flags' => self::FLAG_BOOL,
            ],

            'required' => [
                'name' => 'required',
                'flags' => self::FLAG_BOOL,
            ],
        ];
    }

    public static function fieldTypeToClassName(string $type): string
    {
        return __NAMESPACE__.'\\Models\\Fields\\'.implode('', array_map('ucfirst', explode('_', $type)));
    }

    public static function fieldTypeToAttributeTableName(string $type): string
    {
        return "tbl_fields_{$type}";
    }

    public static function loadFromElementName(string $elementName, string $sectionHandle): AbstractTableModel
    {
        $query = SymphonyPDO\Loader::instance()->prepare(
            'SELECT f.*, s.handle
            FROM `tbl_fields` as `f`
            LEFT JOIN `tbl_sections` as `s` on f.parent_section = s.id
            WHERE f.element_name = :elementName AND s.handle = :sectionHandle
            LIMIT 1'
        );
        $query->bindParam(':elementName', $elementName, \PDO::PARAM_STR);
        $query->bindParam(':sectionHandle', $sectionHandle, \PDO::PARAM_STR);
        $query->execute();
        $id = $query->fetchColumn();

        if (false === $id) {
            throw new Exceptions\NoSuchFieldException("Unable to locate field with element name '{$elementName}'.");
        }

        return self::loadFromId((int) $id);
    }

    public static function loadFromId(int $id): AbstractTableModel
    {
        $db = SymphonyPDO\Loader::instance();

        $query = $db->prepare(sprintf(
            'SELECT * FROM `%s` WHERE `id` = :id LIMIT 1',
            static::TABLE
        ));
        $query->bindParam(':id', $id, \PDO::PARAM_INT);
        $query->execute();
        $basics = $query->fetch(\PDO::FETCH_ASSOC);

        if (false === $basics) {
            throw new Exceptions\NoSuchFieldException("Unable to locate field with id '{$id}'.");
        }

        $class = self::fieldTypeToClassName($basics['type']);
        $attributeTable = self::fieldTypeToAttributeTableName($basics['type']);

        $query = $db->prepare(sprintf(
            'SELECT a.*, f.*
            FROM `%s` as `f`
            INNER JOIN `%s` as `a` ON a.field_id = f.id
            WHERE f.id = :id
            LIMIT 1',
            static::TABLE,
            $attributeTable
        ));
        $query->bindParam(':id', $id, \PDO::PARAM_INT);
        $query->execute();

        $field = (new ResultIterator(
            $class,
            $query
        ))->current();

        if (!($field instanceof self)) {
            throw new Exceptions\CorruptFieldException("Unable to load field with ID {$id}. Something appears to be wrong with the attributes table record for this field. Does it exist?");
        }

        return $field;
    }

    protected function findNextSortOrderValue(): int
    {
        $query = SymphonyPDO\Loader::instance()->prepare(sprintf('SELECT MAX(`sortorder`) + 1 as `value` FROM `%s` WHERE `parent_section` = %d', self::TABLE, (int) $this->sectionId->value));
        $result = $query->execute();
        $sortOrder = (int) $query->fetchColumn();

        return null === $sortOrder ? 0 : $sortOrder;
    }

    public function getDatabaseReadyData(): array
    {
        return [
            'id' => $this->id->value,
            'label' => (string) $this->label,
            'element_name' => (string) $this->elementName,
            'type' => static::TYPE,
            'parent_section' => (int) $this->sectionId->value,
            'sortorder' => (
                null == $this->sortOrder->value
                    ? self::findNextSortOrderValue()
                    : $this->sortOrder->value
            ),
            'location' => (string) $this->location,
            'show_column' => (
                $this->showColumn->value
                    ? 'yes'
                    : 'no'
            ),
            'required' => (
                $this->required->value
                    ? 'yes'
                    : 'no'
            ),
        ];
    }

    public function commit(): AbstractTableModel
    {
        $field = &$this;

        SymphonyPDO\Loader::instance()->doInTransaction(
            function (SymphonyPDO\Lib\Database $db) use ($field) {
                $id = $db->insertUpdate(
                    self::getDatabaseReadyData(),
                    [
                        'label',
                        'element_name',
                        'parent_section',
                        'sortorder',
                        'location',
                        'show_column',
                        'required',
                    ],
                    self::TABLE
                );

                // This is a new field. It does not know it's own ID yet.
                // The call to insertUpdate() will have returned the new
                // ID value, so, set that in the field.
                if (null == $field->id->value) {
                    $field->id((int) $id);

                // This field already has an ID which means an UPDATE
                // call was made (as opposed to an INSERT). Thus, $id will be 0.
                // To avoid issues later, pull out the existing field ID and
                // assign it to $id.
                } else {
                    $id = $field->id->value;
                }

                // Save the field attributes
                $db->delete(static::TABLE, sprintf('`field_id` = %d', (int) $id));
                $db->insert(static::getDatabaseReadyData(), static::TABLE);

                return true;
            }
        );

        // Make sure there is an entries data table for this field. Note this
        // is a DDL query and will automatically end any open transaction we
        // might have going on. We cannot call installEntriesDataTable() if that
        // is the case. The caller will need to handle that themself.
        if (!SymphonyPDO\Loader::instance()->isOpenTransactions()) {
            $field->installEntriesDataTable();
        }

        return $this;
    }
}
