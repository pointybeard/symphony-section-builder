<?php

declare(strict_types=1);

namespace pointybeard\Symphony\SectionBuilder\Traits;

use pointybeard\Symphony\SectionBuilder;

trait hasFetchAssociatedFieldTrait
{
    protected function fetchAssociatedField(string $fieldName): SectionBuilder\AbstractField
    {
        if (!isset($this->$fieldName)) {
            throw new SectionBuilder\Exceptions\SectionBuilderException("{$fieldName} does not exist");
        }

        $value = $this->$fieldName->value;

        if ($value instanceof SectionBuilder\AbstractField) {
            $field = $value;
        } elseif ($value instanceof PropertyBag) {
            try {
                $field = SectionBuilder\AbstractField::loadFromId((int) $value->value);
            } catch (\Exception $ex) {
                var_dump($ex);
                die;
            }
        } else {
            throw new SectionBuilder\Exceptions\SectionBuilderException(self::class."::{$fieldName} is not an instance of AbstractField or PropertyBag");
        }

        return $field;
    }
}
