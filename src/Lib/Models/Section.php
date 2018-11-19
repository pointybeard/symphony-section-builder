<?php
namespace pointybeard\Symphony\SectionBuilder\Lib\Models;

use pointybeard\Symphony\SectionBuilder\Lib\AbstractField;
use pointybeard\PropertyBag\Lib;
use SymphonyPDO\Lib\ResultIterator;
use pointybeard\Symphony\SectionBuilder\Lib\AbstractTableModel;
use pointybeard\Symphony\SectionBuilder\Lib\Exceptions;

class Section extends AbstractTableModel
{
    protected $fields = [];
    protected $isFieldsInitialised = false;
    const TABLE = 'tbl_sections';

    public static function getFieldMappings()
    {
        return (object)[
            'id' => [
                'name' => 'id',
                'flags' => self::FLAG_INT | self::FLAG_IMMUTABLE
            ],

            'name' => [
                'name' => 'name',
                'flags' => self::FLAG_STR
            ],

            'handle' => [
                'name' => 'handle',
                'flags' => self::FLAG_STR
            ],

            'sortorder' => [
                'name' => 'sortOrder',
                'flags' => self::FLAG_INT
            ],

            'hidden' => [
                'name' => 'hideFromBackendNavigation',
                'flags' => self::FLAG_BOOL,
                'default' => false
            ],

            'filter' => [
                'name' => 'allowFiltering',
                'flags' => self::FLAG_BOOL,
                'default' => true
            ],

            'navigation_group' => [
                'name' => 'navigationGroup',
                'flags' => self::FLAG_STR,
                'default' => 'Content'
            ],

            'author_id' => [
                'name' => 'authorId',
                'flags' => self::FLAG_INT,
                'default' => 1
            ],

            'modification_author_id' => [
                'name' => 'modificationAuthorId',
                'flags' => self::FLAG_INT,
                'default' => 1
            ],

            'creation_date' => [
                'name' => 'dateCreatedAt',
                'flags' => self::FLAG_DATE,
                'default' => date('c')
            ],

            'creation_date_gmt' => [
                'name' => 'dateCreatedAtGMT',
                'flags' => self::FLAG_DATE,
                'default' => gmdate('c')
            ],

            'modification_date' => [
                'name' => 'dateModifiedAt',
                'flags' => self::FLAG_DATE,
                'default' => date('c')
            ],

            'modification_date_gmt' => [
                'name' => 'dateModifiedAtGMT',
                'flags' => self::FLAG_DATE,
                'default' => gmdate('c')
            ]
        ];
    }

    public static function loadFromName($name)
    {
        $db = \Symphony::database();

        $query = $db->prepare(sprintf('SELECT * FROM `%s` WHERE `name` = :name LIMIT 1', self::TABLE));
        $query->bindParam(':name', $name, \PDO::PARAM_STR);
        $result = $query->execute();

        return (new ResultIterator(
            get_called_class(),
            $query
        ))->current();
    }

    public static function loadFromHandle($handle)
    {
        $db = \Symphony::database();

        $query = $db->prepare(sprintf('SELECT * FROM `%s` WHERE `handle` = :handle LIMIT 1', self::TABLE));
        $query->bindParam(':handle', $handle, \PDO::PARAM_STR);
        $result = $query->execute();

        return (new ResultIterator(
            get_called_class(),
            $query
        ))->current();
    }

    protected function findNextSortOrderValue()
    {
        $query = \Symphony::database()->prepare('SELECT MAX(`sortorder`) + 1 as `value` FROM ' . self::TABLE);
        $result = $query->execute();
        return max(1, (int)$query->fetchColumn());
    }

    public function getDatabaseReadyData()
    {
        return [
            'id' => $this->id->value,
            'name' => (string)$this->name,
            'handle' => (string)$this->handle,
            'sortorder' => (
                $this->sortOrder->value == null
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
            'navigation_group' => (string)$this->navigationGroup,
            'author_id' => $this->authorId->value,
            'modification_author_id' => $this->modificationAuthorId->value,
            'creation_date' => date('Y-m-d H:i:s', strtotime((string)$this->dateCreatedAt)),
            'creation_date_gmt' => date('Y-m-d H:i:s', strtotime((string)$this->dateCreatedAtGMT)),
            'modification_date' => date('Y-m-d H:i:s', strtotime((string)$this->dateModifiedAt)),
            'modification_date_gmt' => date('Y-m-d H:i:s', strtotime((string)$this->dateModifiedAtGMT)),
        ];
    }

    public function commit()
    {
        $this->initiliseExistingFields();

        $db = \Symphony::database();
        $db->beginTransaction();

        try {
            $id = (int)$db->insertUpdate(
                $this->getDatabaseReadyData(),
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

            if (!($this->id instanceof Lib\ImmutableProperty) && $this->id->value == null) {
                $this->id($id);
            }

            for ($ii = 0; $ii < count($this->fields); $ii++) {
                $this->fields[$ii]
                    ->sectionId((int)$this->id->value)
                    ->commit()
                ;
            }

            $db->commit();
        } catch (\PDOException $ex) {
            $db->rollBack();
            throw $ex;
        }
        return $this;
    }

    protected function initiliseExistingFields()
    {
        $sectionId = (int)$this->id->value;

        // Only try to initialise fields if this section has an id (i.e. it's
        // an existing section, not a new one). We also cannot initialise fields
        // if there are already fields added.
        if (!is_null($sectionId) && $this->isFieldsInitialised != true) {
            $this->isFieldsInitialised = true;

            $db = \Symphony::database();
            $query = $db->prepare(sprintf('SELECT `id` FROM `%s` WHERE `parent_section` = :sectionId', AbstractField::TABLE));
            $query->bindParam(':sectionId', $sectionId, \PDO::PARAM_INT);
            $result = $query->execute();

            while ($fieldId = $query->fetchColumn()) {
                $this->addField(AbstractField::loadFromId($fieldId));
            }
        }

        return true;
    }

    public function fields()
    {
        $this->initiliseExistingFields();
        return $this->fields;
    }

    public function & findFieldByElementName($elementName)
    {
        // Make sure existing fields area already initilised
        $this->initiliseExistingFields();
        for ($ii = 0; $ii < count($this->fields); $ii++) {
            if ((string)$this->fields[$ii]->elementName == $elementName) {
                return $this->fields[$ii];
            }
        }
        throw new Exceptions\NoSuchFieldException("Unable to locate field with element name '{$elementName}' in section.");
    }

    public function & findFieldById($id)
    {
        // Make sure existing fields area already initilised
        $this->initiliseExistingFields();
        for ($ii = 0; $ii < count($this->fields); $ii++) {
            if ((int)$this->fields[$ii]->id->value == $id) {
                return $this->fields[$ii];
            }
        }
        throw new Exceptions\NoSuchFieldException("Unable to locate field with id '{$id}' in section.");
    }

    public function addField(AbstractField $field)
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
