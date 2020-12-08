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

namespace pointybeard\Symphony\SectionBuilder\Traits;

use pointybeard\Symphony\SectionBuilder;

trait HasFetchAssociatedFieldTrait
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
