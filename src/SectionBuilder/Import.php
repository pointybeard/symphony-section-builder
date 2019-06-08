<?php

declare(strict_types=1);

namespace pointybeard\Symphony\SectionBuilder\SectionBuilder;

use pointybeard\Helpers\Functions\Flags;

class Import
{
    const FLAG_SKIP_ORDERING = 0x0001;

    protected static function hasAssociations(\StdClass $section)
    {
        return
            isset($section->associations) && count($section->associations) > 0
        ;
    }

    protected static function hasDynamicOptions(\StdClass $field): bool
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
                throw new \Exception('Looks like you might have a circular field dependency...'.PHP_EOL.json_encode($associations, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
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

    public static function fromJsonFile(string $file, int $flags = null): array
    {
        if (!is_readable($file)) {
            throw new \Exception(sprintf(
                "The file '%s' is not readable.",
                $file
            ));
        }

        return self::fromJsonString(file_get_contents($file), $flags);
    }

    public static function fromJsonString(string $string, int $flags = null): array
    {
        $json = json_decode($string);
        if (false == $json || null === $json) {
            throw new \Exception(
                'String is not a valid JSON document.'
            );
        }

        // @todo: Validate json against schema

        return self::fromObject($json, $flags);
    }

    public static function fromObject(\StdClass $data, int $flags = null): array
    {
        // Sometimes it might be necessary to skip the ordering step.
        // This is important when importing partial section JSON
        // since it will often trigger a circular dependency exception.
        $sections = Flags\is_flag_set($flags, self::FLAG_SKIP_ORDERING)
            ? $data->sections
            : self::orderSectionsByAssociations($data->sections)
        ;

        $result = [];
        foreach ($sections as $s) {
            $s->dateCreatedAt = date(
                'c',
                isset($s->dateCreatedAt)
                ? strtotime($s->dateCreatedAt)
                : time()
            );

            $s->dateCreatedAtGMT = gmdate(
                'c',
                isset($s->dateCreatedAtGMT)
                ? strtotime($s->dateCreatedAtGMT)
                : time()
            );

            $s->dateModifiedAt = date(
                'c',
                isset($s->dateModifiedAt)
                ? strtotime($s->dateModifiedAt)
                : time()
            );

            $s->dateModifiedAtGMT = gmdate(
                'c',
                isset($s->dateModifiedAtGMT)
                ? strtotime($s->dateModifiedAtGMT)
                : time()
            );

            // authorId and modificationAuthorId are optional field but they
            // must be set still. The primary author if for any symphony install
            // is 1, so we'll use that.
            $s->authorId = $s->authorId ?? 1;
            $s->modificationAuthorId = $s->modificationAuthorId ?? 1;

            $section = Models\Section::loadFromHandle($s->handle);
            if (!($section instanceof Models\Section)) {
                $section = (new Models\Section())
                    ->name($s->name)
                    ->handle($s->handle)
                    ->sortOrder($s->sortOrder)
                    ->hideFromBackendNavigation($s->hideFromBackendNavigation)
                    ->allowFiltering($s->allowFiltering)
                    ->navigationGroup($s->navigationGroup)
                    ->authorId($s->authorId)
                    ->modificationAuthorId($s->modificationAuthorId)
                    ->dateCreatedAt($s->dateCreatedAt)
                    ->dateCreatedAtGMT($s->dateCreatedAtGMT)
                    ->dateModifiedAt($s->dateModifiedAt)
                    ->dateModifiedAtGMT($s->dateModifiedAtGMT)
                ;

                // This will help us handle circular dependancies later.
                $deferred = [];

                foreach ($s->fields as $f) {
                    $deferField = false;

                    $class = AbstractField::fieldTypeToClassName($f->type);

                    if (!class_exists($class)) {
                        throw new \Exception("Unable to locate Field model class for field type {$f->type}.");
                    }

                    $field = (new $class())
                        ->label($f->label)
                        ->elementName($f->elementName)
                        ->location($f->location)
                        ->required($f->required)
                        ->showColumn($f->showColumn)
                    ;

                    foreach ((array) $f->custom as $key => $value) {
                        // Look to see if this custom item is a reference to
                        // section and field
                        if ($value instanceof \StdClass) {
                            if (!isset($value->section) || !isset($value->field)) {
                                // Hmm, we cannot handle this yet. Throw an exception
                                throw new \Exception("Unable to handle field of type {$f->type}. It contains non-standard objects in the custom values object.");
                            }

                            // Find the related section
                            $relatedSection = Models\Section::loadFromHandle($value->section);
                            if (!($relatedSection instanceof Models\Section)) {
                                // This might be due to self dependency (i.e.
                                // the relationship is from a field in this
                                // section to another field in this section.
                                if ($value->section == $section->handle) {
                                    // We'll shelve this dependency until the
                                    // section has been created.
                                    $deferred[$f->elementName] = (object) [
                                        'property' => $key,
                                        'relationship' => (object) [
                                            'section' => $value->section,
                                            'field' => $value->field,
                                        ],
                                        'field' => null,
                                    ];
                                    $deferField = true;
                                    $value = null;
                                } else {
                                    throw new \Exception("Unable to locate section with handle {$value->section} ...".PHP_EOL.json_encode($f->custom, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                                }
                            } else {
                                // Force the section to load up it's fields
                                $relatedSection->fields();

                                // Find the related field
                                $relatedField = $relatedSection->findFieldByElementName($value->field);
                                if (!($relatedField instanceof AbstractField)) {
                                    throw new \Exception("Unable to locate field with element name {$value->field} ...".PHP_EOL.json_encode($f->custom, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                                }

                                $value = $relatedField;
                            }
                        }

                        $field->$key($value);
                    }

                    if (true == $deferField) {
                        $deferred[$f->elementName]->field = $field;
                    } else {
                        $section->addField($field);
                    }
                }

                $section->commit();

                // Handle deferred field relationships
                if (!empty($deferred)) {
                    foreach ($deferred as $elementName => $d) {
                        $d->field->{$d->property}(
                            Models\Section::loadFromHandle($d->relationship->section)
                            ->findFieldByElementName($d->relationship->field)
                        );

                        $section->addField($d->field)->commit();
                    }
                }

                $result[] = $section;
            }
        }

        return $result;
    }
}
