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
use pointybeard\PropertyBag\Lib\PropertyBag;
use SymphonyPDO;
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

    public const FLAG_EXCLUDE_IDS = 0x0400;
    public const FLAG_EXCLUDE_SORTORDER = 0x0800;
    public const FLAG_EXCLUDE_AUTHOR_IDS = 0x1000;
    public const FLAG_EXCLUDE_DATES = 0x2000;
    public const FLAG_LESS = self::FLAG_EXCLUDE_IDS | self::FLAG_EXCLUDE_SORTORDER | self::FLAG_EXCLUDE_AUTHOR_IDS | self::FLAG_EXCLUDE_DATES;

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
                $value = ('yes' == strtolower((string) $value) || true === $value);
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

        $query = $db->prepare(sprintf('SELECT * FROM `%s` ORDER BY `sortorder` ASC', static::TABLE));
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

    protected static function recursiveRemoveFieldFromArray(array $data, ?int $flags) {

        if (true == Flags\is_flag_set($flags, self::FLAG_EXCLUDE_IDS)) {
            unset($data['id']);
            unset($data['sectionId']);
        }

        if (true == Flags\is_flag_set($flags, self::FLAG_EXCLUDE_SORTORDER)) {
            unset($data['sortOrder']);
        }

        if (true == Flags\is_flag_set($flags, self::FLAG_EXCLUDE_AUTHOR_IDS)) {
            unset($data['authorId']);
            unset($data['modificationAuthorId']);
        }

        if (true == Flags\is_flag_set($flags, self::FLAG_EXCLUDE_DATES)) {
            unset($data['dateCreatedAt']);
            unset($data['dateCreatedAtGMT']);
            unset($data['dateModifiedAt']);
            unset($data['dateModifiedAtGMT']);
        }

        foreach ($data as $name => $properties) {
            if (true == is_array($properties)) {
                $data[$name] = self::recursiveRemoveFieldFromArray($properties, $flags);
            }
        }

        return $data;
    }

    protected static function removeIdFromArray(array $data): array
    {
        return self::recursiveRemoveFieldFromArray($data, self::FLAG_EXCLUDE_IDS);
    }

    public function __toJson(?int $flags = self::FLAG_EXCLUDE_IDS): string
    {
        $data = $this->__toArray();

        if (null !== $flags) {
            $data = self::recursiveRemoveFieldFromArray($data, $flags);
        }

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function __toString()
    {
        return $this->__toJson();
    }
}
