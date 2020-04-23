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

abstract class AbstractOperation implements Interfaces\OperationInterface
{
    protected static function hasAssociations(\stdClass $section)
    {
        return
            isset($section->associations) && count($section->associations) > 0
        ;
    }

    protected static function hasDynamicOptions(\stdClass $field): bool
    {
        return
            isset($field->custom) &&
            isset($field->custom->dynamicOptions) &&
            isset($field->custom->dynamicOptions->section)
        ;
    }

    public static function orderSectionsByAssociations(array $sections): array
    {
        $associations = [];
        foreach ($sections as $s) {
            $associations[$s->handle] = [];

            if (self::hasAssociations($s)) {
                foreach ($s->associations as $a) {
                    $associations[$s->handle][] = $a->parent->section;
                }
            }

            foreach ($s->fields as $f) {
                if (self::hasDynamicOptions($f)) {
                    $associations[$s->handle][] = $f->custom->dynamicOptions->section;
                }
            }
        }

        $associationsOrdered = [];
        $lastCount = count($associations);
        while (!empty($associations)) {
            foreach ($associations as $handle => $a) {
                // Iterate over each item in $a and see if they are all in
                // $associationsOrdered
                $allFound = true;
                if (!empty($a)) {
                    foreach ($a as $h) {
                        if ($handle == $h) {
                            // Hmm, this means the section links to itself.
                            continue;
                        }

                        if (!isset($associationsOrdered[$h])) {
                            $allFound = false;
                            break;
                        }
                    }
                }

                if (true == $allFound) {
                    $associationsOrdered[$handle] = $a;
                    unset($associations[$handle]);
                }
            }

            if ($lastCount == count($associations)) {
                throw new Exceptions\SectionBuilderException('Looks like you might have a circular field dependency...'.PHP_EOL.json_encode($associations, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            }

            $lastCount = count($associations);
        }

        // Iterate over the, now ordered, list of sections to rebuild the
        // sections array
        $sectionsOrdered = [];
        foreach (array_keys($associationsOrdered) as $handle) {
            foreach ($sections as $s) {
                if ($s->handle == $handle) {
                    $sectionsOrdered[] = $s;

                    continue 2;
                }
            }
        }

        return $sectionsOrdered;
    }
}
