<?php

declare(strict_types=1);

namespace pointybeard\Symphony\SectionBuilder;

use SymphonyPDO;
use SymphonyPDO\Lib\ResultIterator;
use pointybeard\Helpers\Functions\Flags;
use pointybeard\PropertyBag\Lib\PropertyBag;

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

    use Traits\hasToStringToJsonTrait;

    protected static $databaseFieldMapping = [];

    abstract public static function getFieldMappings(): \stdClass;

    abstract public function getDatabaseReadyData(): array;

    abstract public function commit(): self;

    abstract public function __toArray(): array;

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

    public function delete()
    {
        $table = static::TABLE;
        $id = (int) $this->id->value;

        return SymphonyPDO\Loader::instance()->doInTransaction(
            function (SymphonyPDO\Lib\Database $db) use ($table, $id) {
                return $db->delete($table, sprintf(
                    '`id` = %d',
                    $id
                ));
            }
        );
    }

    private function getCallingMethod(int $depth = 2): string
    {
        return debug_backtrace()[$depth]['function'];
    }

    protected static function enforceType($value, int $flags)
    {
        if (Flags\is_flag_set($flags, self::FLAG_NULL) && empty($value)) {
            $value = null;
        } else {
            if (Flags\is_flag_set($flags, self::FLAG_BOOL)) {
                $value = ('yes' == strtolower($value) || true === $value);
            } elseif (Flags\is_flag_set($flags, self::FLAG_INT)) {
                $value = (int) $value;
            } elseif (Flags\is_flag_set($flags, self::FLAG_STR)) {
                $value = (string) $value;
            } elseif (Flags\is_flag_set($flags, self::FLAG_FLOAT)) {
                $value = (float) $value;
            } elseif (Flags\is_flag_set($flags, self::FLAG_DATE)) {
                $value = date('c', strtotime($value));
            } elseif (Flags\is_flag_set($flags, self::FLAG_CURRENCY)) {
                $value = (float) number_format((float) $value, 2, null, null);
            } elseif (Flags\is_flag_set($flags, self::FLAG_FIELD)) {
                $value = AbstractField::loadFromId((int) $value);
            } elseif (Flags\is_flag_set($flags, self::FLAG_SECTION)) {
                $value = Models\Section::loadFromId((int) $value);
            }
        }

        return $value;
    }

    public function __isset($name)
    {
        return isset($this->properties[$name]);
    }

    public function __set($property, $value)
    {
        $name = $property;

        if ('fetch' == $this->getCallingMethod() && isset(static::getFieldMappings()->{$property})) {
            $mapping = static::getFieldMappings()->{$property};

            if (isset($mapping['name'])) {
                $name = $mapping['name'];
            }

            if (isset($mapping['flags'])) {
                $value = self::enforceType($value, $mapping['flags']);
            }
        }

        return parent::__set($name, $value);
    }

    public static function all(): ResultIterator
    {
        $db = SymphonyPDO\Loader::instance();

        $query = $db->prepare(sprintf('SELECT * FROM `%s`', static::TABLE));
        $result = $query->execute();

        return new ResultIterator(
            get_called_class(),
            $query
        );
    }

    public static function loadFromId(int $id): self
    {
        $db = SymphonyPDO\Loader::instance();

        $query = $db->prepare(sprintf('SELECT * FROM `%s` WHERE `id` = :id LIMIT 1', static::TABLE));
        $query->bindParam(':id', $id, \PDO::PARAM_INT);
        $result = $query->execute();

        return (new ResultIterator(
            static::class,
            $query
        ))->current();
    }
}
