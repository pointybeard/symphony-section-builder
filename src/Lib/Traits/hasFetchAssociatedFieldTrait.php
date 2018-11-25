<?php
namespace pointybeard\Symphony\SectionBuilder\Lib\Traits;

use pointybeard\Symphony\SectionBuilder\Lib;

trait hasFetchAssociatedFieldTrait
{
    protected function fetchAssociatedField($field)
    {
        return (
            $this->$field->value instanceof Lib\AbstractField
                ? $this->$field->value
                : Lib\AbstractField::loadFromId((int)$this->$field->value)
        );
    }
}
