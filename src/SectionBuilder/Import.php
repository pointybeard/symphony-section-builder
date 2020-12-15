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

class Import extends AbstractOperation
{
    const FLAG_SKIP_ORDERING = 0x0001;

    public static function fromJsonFile(string $file, int $flags = null): array
    {
        if (!is_readable($file)) {
            throw new Exceptions\SectionBuilderException(sprintf("The file '%s' is not readable.", $file));
        }

        return self::fromJsonString(file_get_contents($file), $flags);
    }

    public static function fromJsonString(string $string, int $flags = null): array
    {
        $json = json_decode($string);
        if (false == $json || null === $json) {
            throw new Exceptions\SectionBuilderException('String is not a valid JSON document.');
        }

        // @todo: Validate json against schema

        return self::fromObject($json, $flags);
    }

    public static function fromObject(\stdClass $data, int $flags = null): array
    {
        // Sometimes it might be necessary to skip the ordering step.
        // This is important when importing partial section JSON
        // since it will often trigger a circular dependency exception.
        $sections = Flags\is_flag_set($flags, self::FLAG_SKIP_ORDERING)
            ? $data->sections
            : self::orderSectionsByAssociations($data->sections)
        ;

        $result = [];
        foreach ($sections as $index => $s) {
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
                    ->sortOrder($s->sortOrder ?? $index)
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

                foreach ($s->fields as $ii => $f) {
                    $deferField = false;

                    $class = AbstractField::fieldTypeToClassName($f->type);

                    if (!class_exists($class)) {
                        throw new Exceptions\SectionBuilderException("Unable to locate Field model class for field type {$f->type}.");
                    }

                    $field = (new $class())
                        ->label($f->label)
                        ->elementName($f->elementName)
                        ->location($f->location)
                        ->required($f->required)
                        ->sortOrder($s->sortOrder ?? $index)
                        ->showColumn($f->showColumn)
                    ;

                    foreach ((array) $f->custom as $key => $value) {
                        // Look to see if this custom item is a reference to
                        // section and field
                        if ($value instanceof \stdClass) {
                            if (!isset($value->section) || !isset($value->field)) {
                                // Hmm, we cannot handle this yet. Throw an exception
                                throw new Exceptions\SectionBuilderException("Unable to handle field of type {$f->type}. It contains non-standard objects in the custom values object.");
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
                                    throw new Exceptions\SectionBuilderException("Unable to locate section with handle {$value->section} ...".PHP_EOL.json_encode($f->custom, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                                }
                            } else {
                                // Force the section to load up it's fields
                                $relatedSection->fields();

                                // Find the related field
                                $relatedField = $relatedSection->findFieldByElementName($value->field);
                                if (!($relatedField instanceof AbstractField)) {
                                    throw new Exceptions\SectionBuilderException("Unable to locate field with element name {$value->field} ...".PHP_EOL.json_encode($f->custom, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
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
