<?php

declare(strict_types=1);

namespace pointybeard\Symphony\SectionBuilder\SectionBuilder\Traits;

use pointybeard\PropertyBag\Lib as PropertyBag;
use pointybeard\Symphony\SectionBuilder\SectionBuilder;

trait hasFetchAssociatedFieldTrait
{
    protected function fetchAssociatedField(PropertyBag $field)
    {
        return
            $this->$field->value instanceof SectionBuilder\AbstractField
                ? $this->$field->value
                : SectionBuilder\AbstractField::loadFromId((int) $this->$field->value->value)
        ;
    }
}
