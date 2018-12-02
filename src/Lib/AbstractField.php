<?php
namespace pointybeard\Symphony\SectionBuilder\Lib;

use pointybeard\PropertyBag\Lib;
use pointybeard\Symphony\SectionBuilder\Lib\AbstractTableModel;
use pointybeard\Symphony\SectionBuilder\Lib\Interfaces;
use pointybeard\Symphony\SectionBuilder\Lib\Models\Fields;
use pointybeard\Symphony\SectionBuilder\Lib as SectionBuilder;

use SymphonyPDO\Lib\ResultIterator;

abstract class AbstractField extends SectionBuilder\AbstractTableModel
{
    const PLACEMENT_MAIN_CONTENT = 'main';
    const PLACEMENT_SIDEBAR = 'sidebar';
    const TABLE = "tbl_fields";

    public function __construct()
    {
        // Set up field so we don't get errors later
        foreach (static::getFieldMappings() as $m) {
            $this->{$m['name']}(null);
        }
    }

    public function hasAssociations()
    {
        return ($this instanceof Interfaces\FieldAssociationInterface);
    }

    public function installEntriesDataTable()
    {
        \SymphonyPDO\Loader::instance()->exec(
            static::getEntriesDataCreateTableSyntax()
        );
        return true;
    }

    public function __toArray() {
        $mapping = static::getFieldMappings();
        $baseData = self::getDatabaseReadyData();
        $customData = static::getDatabaseReadyData();

        $output = [
            'custom' => []
        ];
        foreach($mapping as $name => $properties) {
            if(isset($baseData[$name])) {
                // Add this to the start of the array.
                $output = [
                    $properties['name'] => $this->{$properties['name']}->value
                ] + $output;
            } elseif(isset($customData[$name])) {
                if(SectionBuilder\AbstractTableModel::isFlagSet($properties['flags'], self::FLAG_FIELD)) {
                    $associatedField = $this->fetchAssociatedField($properties['name']);
                    $output['custom'][$properties['name']] = [
                        "section" => (string)$associatedField->section()->handle,
                        "field" => (string)$associatedField->elementName,
                    ];
                } else {
                    $output['custom'][$properties['name']] = $this->{$properties['name']}->value;
                }
            }
        }

        return $output;
    }

    public function section() {
        if($this->sectionId->value == null) {
            return false;
        }
        return SectionBuilder\Models\Section::loadFromId($this->sectionId->value);
    }

    public static function getFieldMappings()
    {
        return (object)[
            'id' => [
                'name' => 'id',
                'flags' => self::FLAG_INT | self::FLAG_IMMUTABLE
            ],

            'type' => [
                'name' => 'type',
                'flags' => self::FLAG_STR
            ],

            'label' => [
                'name' => 'label',
                'flags' => self::FLAG_STR
            ],

            'element_name' => [
                'name' => 'elementName',
                'flags' => self::FLAG_STR
            ],

            'parent_section' => [
                'name' => 'sectionId',
                'flags' => self::FLAG_INT
            ],

            'sortorder' => [
                'name' => 'sortOrder',
                'flags' => self::FLAG_INT
            ],

            'location' => [
                'name' => 'location',
                'flags' => self::FLAG_STR
            ],

            'show_column' => [
                'name' => 'showColumn',
                'flags' => self::FLAG_BOOL
            ],

            'required' => [
                'name' => 'required',
                'flags' => self::FLAG_BOOL
            ],

        ];
    }

    public static function fieldTypeToClassName($type)
    {
        return __NAMESPACE__ . '\\Models\\Fields\\' . implode("", array_map("ucfirst", explode('_', $type)));
    }

    public static function fieldTypeToAttributeTableName($type)
    {
        return "tbl_fields_{$type}";
    }

    public static function loadFromElementName($elementName)
    {
        $query = \SymphonyPDO\Loader::instance()->prepare(sprintf(
            'SELECT * FROM `%s` WHERE `element_name` = :elementName LIMIT 1',
            static::TABLE
        ));
        $query->bindParam(':elementName', $elementName, \PDO::PARAM_STR);
        $query->execute();
        $id = $query->fetchColumn();

        if($id === false) {
            throw new Exceptions\NoSuchFieldException("Unable to locate field with element name '{$elementName}'.");
        }

        return self::loadFromId($id);
    }

    public static function loadFromId($id)
    {
        $db = \SymphonyPDO\Loader::instance();

        $query = $db->prepare(sprintf(
            'SELECT * FROM `%s` WHERE `id` = :id LIMIT 1',
            static::TABLE
        ));
        $query->bindParam(':id', $id, \PDO::PARAM_INT);
        $query->execute();
        $basics = $query->fetch(\PDO::FETCH_ASSOC);

        if($basics === false) {
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

        return (new ResultIterator(
            $class,
            $query
        ))->current();
    }

    protected function findNextSortOrderValue()
    {
        $query = \SymphonyPDO\Loader::instance()->prepare(sprintf('SELECT MAX(`sortorder`) + 1 as `value` FROM `%s` WHERE `parent_section` = %d', self::TABLE, (int)$this->sectionId->value));
        $result = $query->execute();
        $sortOrder = (int)$query->fetchColumn();

        return is_null($sortOrder) ? 0 : $sortOrder;
    }

    public function getDatabaseReadyData()
    {
        return [
            'id' => $this->id->value,
            'label' => (string)$this->label,
            'element_name' => (string)$this->elementName,
            'type' => static::TYPE,
            'parent_section' => (int)$this->sectionId->value,
            'sortorder' => (
                $this->sortOrder->value == null
                    ? self::findNextSortOrderValue()
                    : $this->sortOrder->value
            ),
            'location' => (string)$this->location,
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

    public function commit()
    {
        $field =& $this;

        \SymphonyPDO\Loader::instance()->doInTransaction(
            function (\SymphonyPDO\Lib\Database $db) use ($field) {
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

                if ($field->id->value == null) {
                    $field->id((int)$id);
                }

                // Save the field attributes
                $db->delete(static::TABLE, sprintf("`field_id` = %d", (int)$id));
                $db->insert(static::getDatabaseReadyData(), static::TABLE);

                return true;
            }
        );


        // Make sure there is an entries data table for this field. Note this
        // is a DDL query and will automatically end any open transaction we
        // might have going on. We cannot call installEntriesDataTable() if that
        // is the case. The caller will need to handle that themself.
        if (!\SymphonyPDO\Loader::instance()->isOpenTransactions()) {
            $field->installEntriesDataTable();
        }

        return $this;
    }
}
