<?php
namespace pointybeard\Symphony\SectionBuilder\Lib\Diff;

class Record
{
    const OP_ADDED = 'added';
    const OP_REMOVED = 'removed';
    const OP_RENAMED = 'renamed';
    const OP_UPDATED = 'updated';

    const TYPE_FIELD = "Field";
    const TYPE_SECTION = "Section";

    private $op;
    private $type;
    private $context;
    private $nameOriginal;
    private $nameNew;
    private $valueOriginal;
    private $valueNew;

    public function __get($name){
        return $this->$name;
    }

    public function __set($name, $value) {
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
