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

trait hasToStringToJsonTrait
{
    protected function removeIdFromArray(array $data): array
    {
        unset($data['id']);
        unset($data['sectionId']);
        foreach ($data as $name => $properties) {
            if (is_array($properties)) {
                $data[$name] = self::removeIdFromArray($properties);
            }
        }

        return $data;
    }

    public function __toJson(bool $excludeIds = true): string
    {
        $data = $this->__toArray();

        // Lets ignore ID and SectionId
        if ($excludeIds) {
            $data = self::removeIdFromArray($data);
        }

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function __toString()
    {
        return $this->__toJson();
    }
}
