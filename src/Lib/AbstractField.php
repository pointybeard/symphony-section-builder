<?php
namespace pointybeard\Symphony\SectionBuilder\Lib;

use pointybeard\PropertyBag\Lib;
use pointybeard\Symphony\SectionBuilder\Lib\AbstractTableModel;
use SymphonyPDO\Lib\ResultIterator;

abstract class AbstractField extends AbstractTableModel
{
    const PLACEMENT_MAIN_CONTENT = 'main';
    const PLACEMENT_SIDEBAR = 'sidebar';
    const TABLE = "tbl_fields";

    public function __construct()
    {
        // Set up field so we don't get errors later
        foreach (self::getFieldMappings() as $m) {
            $this->{$m['name']}(null);
        }
    }

    public function installEntriesDataTable() {
        $sql = static::getEntriesDataCreateTableSyntax();
        return \SymphonyPDO\Loader::instance()->doInTransaction(
            function(\SymphonyPDO\Lib\Database $db) use ($sql) {
                return $db->exec($sql);
            }
        );
    }

    public static function getFieldMappings()
    {
        return (object)[
            'id' => [
                'name' => 'id',
                'flags' => self::FLAG_INT | self::FLAG_IMMUTABLE
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

    protected static function fieldTypeToClassName($type)
    {
        return __NAMESPACE__ . '\\Models\\Fields\\' . implode("", array_map("ucfirst", explode('_', $type)));
    }

    protected static function fieldTypeToAttributeTableName($type)
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
        return self::loadFromId($query->fetchColumn());
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
        $db = \SymphonyPDO\Loader::instance();

        // Save the core field data
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

        if ($this->id->value == null) {
            $this->id((int)$id);
        }

        // Save the field attributes
        $this->installEntriesDataTable();
        $db->delete(static::TABLE, sprintf("`field_id` = %d", (int)$this->id->value));
        $db->insert(static::getDatabaseReadyData(), static::TABLE);

        return $this;
    }
}
