<?php

namespace pointybeard\Symphony\SectionBuilder\Lib;

use pointybeard\Symphony\SectionBuilder\Lib\AbstractField;
use pointybeard\PropertyBag\Lib\ImmutableProperty;
use pointybeard\PropertyBag\Lib\PropertyBag;
use SymphonyPDO\Lib\ResultIterator;

abstract class AbstractTableModel extends PropertyBag
{
    const FLAG_BOOL = 0x0001;
    const FLAG_INT = 0x0002;
    const FLAG_STR = 0x0004;
    const FLAG_FLOAT = 0x0008;
    const FLAG_NULL = 0x0010;
    const FLAG_DATE = 0x0020;
    const FLAG_CURRENCY = 0x0040;
    const FLAG_IMMUTABLE = 0x0080;
    const FLAG_FIELD = 0x0100;
    const FLAG_SECTION = 0x0200;

    protected static $databaseFieldMapping = [];

    abstract public static function getFieldMappings();
    abstract public function getDatabaseReadyData();
    abstract public function commit();

    public function __construct()
    {
        // Set up field so we don't get errors later
        foreach (static::getFieldMappings() as $m) {
            $this->{$m['name']}(
                isset($m['default'])
                    ? $m['default']
                    : null
            );
        }
    }

    protected static function isFlagSet($flags, $flag)
    {
        // Flags support bitwise operators so it's easy to see
        // if one has been set.
        return ($flags & $flag) == $flag;
    }

    public function delete()
    {
        $table = static::TABLE;
        $id = (int)$this->id->value;
        return \SymphonyPDO\Loader::instance()->doInTransaction(
            function (\SymphonyPDO\Lib\Database $db) use ($table, $id) {
                return $db->delete($table, sprintf(
                    "`id` = %d",
                    $id
                ));
            }
        );
    }

    private function getCallingMethod($depth = 2)
    {
        return debug_backtrace()[$depth]['function'];
    }

    protected static function enforceType($value, $flags)
    {
        if (self::isFlagSet($flags, self::FLAG_NULL) && empty($value)) {
            $value = null;
        } else {
            if (self::isFlagSet($flags, self::FLAG_BOOL)) {
                $value = (strtolower($value) == 'yes' || $value === true);
            } elseif (self::isFlagSet($flags, self::FLAG_INT)) {
                $value = (int)$value;
            } elseif (self::isFlagSet($flags, self::FLAG_STR)) {
                $value = (string)$value;
            } elseif (self::isFlagSet($flags, self::FLAG_FLOAT)) {
                $value = (float)$value;
            } elseif (self::isFlagSet($flags, self::FLAG_DATE)) {
                $value = date('c', strtotime($value));
            } elseif (self::isFlagSet($flags, self::FLAG_CURRENCY)) {
                $value = (float)number_format((float)$value, 2, null, null);
            } elseif (self::isFlagSet($flags, self::FLAG_FIELD)) {
                $value = AbstractField::loadFromId((int)$value);
            } elseif (self::isFlagSet($flags, self::FLAG_SECTION)) {
                $value = Models\Section::loadFromId((int)$value);
            }
        }

        return $value;
    }

    public function __set($property, $value)
    {
        $name = $property;

        if ($this->getCallingMethod() == 'fetch' && isset(static::getFieldMappings()->{$property})) {
            $mapping = static::getFieldMappings()->{$property};

            if (isset($mapping['name'])) {
                $name = $mapping['name'];
            }

            if (isset($mapping['flags'])) {
                $value = self::enforceType($value, $mapping['flags']);
            }

            //if(self::isFlagSet($mapping['flags'], self::FLAG_IMMUTABLE)) {
            //    $this->{$name}(new ImmutableProperty($name, $value));
            //    return true;
            //}
        }
        return parent::__set($name, $value);
    }

    public static function loadFromId($id)
    {
        $db = \SymphonyPDO\Loader::instance();

        $query = $db->prepare(sprintf('SELECT * FROM `%s` WHERE `id` = :id LIMIT 1', static::TABLE));
        $query->bindParam(':id', $id, \PDO::PARAM_INT);
        $result = $query->execute();

        return (new ResultIterator(
            get_called_class(),
            $query
        ))->current();
    }
}
