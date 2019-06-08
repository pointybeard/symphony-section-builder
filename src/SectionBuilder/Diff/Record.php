<?php

declare(strict_types=1);

namespace pointybeard\Symphony\SectionBuilder\Diff;

class Record
{
    public const OP_ADDED = 'added';
    public const OP_REMOVED = 'removed';
    public const OP_RENAMED = 'renamed';
    public const OP_UPDATED = 'updated';

    public const TYPE_FIELD = 'Field';
    public const TYPE_SECTION = 'Section';

    private $op;
    private $type;
    private $context;
    private $nameOriginal;
    private $nameNew;
    private $valueOriginal;
    private $valueNew;

    public function __get($name)
    {
        return $this->$name;
    }

    public function __set($name, $value)
    {
        $this->$name = $value;

        return true;
    }

    public function __call($name, $args)
    {
        if (empty($args)) {
            return $this->$name;
        }

        $this->$name = $args[0];

        return $this;
    }
}
