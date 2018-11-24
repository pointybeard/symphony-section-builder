<?php
namespace pointybeard\Symphony\SectionBuilder\Lib\Models;

use pointybeard\Symphony\SectionBuilder\Lib\AbstractField;
use pointybeard\PropertyBag\Lib;
use SymphonyPDO\Lib\ResultIterator;
use pointybeard\Symphony\SectionBuilder\Lib\AbstractTableModel;
use pointybeard\Symphony\SectionBuilder\Lib\Exceptions;

class SectionAssociation extends AbstractTableModel
{
    protected $fields = [];
    const TABLE = 'tbl_sections_association';

    public static function getFieldMappings()
    {
        return (object)[
            'id' => [
                'name' => 'id',
                'flags' => self::FLAG_INT
            ],

            'parent_section_id' => [
                'name' => 'parentSectionId',
                'flags' => self::FLAG_SECTION
            ],

            'parent_section_field_id' => [
                'name' => 'parentSectionFieldId',
                'flags' => self::FLAG_FIELD | self::FLAG_NULL,
            ],

            'child_section_id' => [
                'name' => 'childSectionId',
                'flags' => self::FLAG_SECTION
            ],

            'child_section_field_id' => [
                'name' => 'childSectionFieldId',
                'flags' => self::FLAG_FIELD
            ],

            'hide_association' => [
                'name' => 'hideAssociation',
                'flags' => self::FLAG_BOOL,
                'default' => false
            ],

            'interface' => [
                'name' => 'interface',
                'flags' => self::FLAG_STR | self::FLAG_NULL
            ],

            'editor' => [
                'name' => 'editor',
                'flags' => self::FLAG_STR | self::FLAG_NULL
            ],
        ];
    }

    private function fetchFieldOrSection($field, $type) {
        return (
            $this->$field->value instanceof $type
                ? $this->$field->value
                : $type::loadFromId($this->$field->value)
        );
    }

    public function parentSection() {
        return $this->fetchFieldOrSection('parentSectionId', __NAMESPACE__ . '\\Section');
    }

    public function childSection() {
        return $this->fetchFieldOrSection('childSectionId', __NAMESPACE__ . '\\Section');
    }

    public function parentSectionField() {
        // Remove the trailing '\Models' part of the namespace for this class
        $namespace = preg_replace("@\\\\Models$@", '', __NAMESPACE__);
        return $this->fetchFieldOrSection(
            'parentSectionFieldId',
            $namespace . '\\AbstractField'
        );
    }

    public function childSectionField() {
        // Remove the trailing \Models part of the namespace for this class
        $namespace = preg_replace("@\\\\Models$@", '', __NAMESPACE__);
        return $this->fetchFieldOrSection(
            'childSectionFieldId',
            $namespace . '\\AbstractField'
        );
    }

    public function getDatabaseReadyData()
    {
        return [
            'interface' => (string)$this->interface,
            'editor' => (string)$this->editor,
            'hide_association' => self::boolToEnumYesNo($this->hideAssociation->value),
            'parent_section_id' => (int)$this->parentSection()->id->value,
            'child_section_id' => (int)$this->childSection()->id->value,
            'parent_section_field_id' => (int)$this->parentSectionField()->id->value,
            'child_section_field_id' => (int)$this->childSectionField()->id->value,
        ];
    }

    protected static function boolToEnumYesNo($value)
    {
        return $value == true ? 'yes' : 'no';
    }

    public static function fetchByChildSectionId($sectionId)
    {
        $query = \SymphonyPDO\Loader::instance()->prepare(sprintf(
            'SELECT * FROM `%s` WHERE `child_section_id` = :sectionId LIMIT 1',
            static::TABLE
        ));
        $query->bindParam(':sectionId', $sectionId, \PDO::PARAM_INT);
        $result = $query->execute();

        return (new ResultIterator(
            get_called_class(),
            $query
        ));
    }

    public function commit()
    {
        $data = self::getDatabaseReadyData();
        $table = self::TABLE;
        \SymphonyPDO\Loader::instance()->doInTransaction(
            function(\SymphonyPDO\Lib\Database $db) use ($table, $data) {
                $db->delete($table, sprintf(
                    "`child_section_id` = %d AND `child_section_field_id` = %d",
                    $data['child_section_id'],
                    $data['child_section_field_id']
                ));
                $db->insert($data, self::TABLE);
                return true;
            }
        );
        return $this;
    }
}
