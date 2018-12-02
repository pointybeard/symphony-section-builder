<?php
namespace pointybeard\Symphony\SectionBuilder\Lib;

class Diff
{
    public static function fromJsonFile($file)
    {
        if (!is_readable($file)) {
            throw new \Exception(sprintf(
                "The file '%s' is not readable.",
                $file
            ));
        }
        return self::fromJsonString(file_get_contents($file));
    }

    public static function fromJsonString($string)
    {
        $json = json_decode($string);
        if ($json == false || is_null($json)) {
            throw new \Exception(
                "String is not a valid JSON document."
            );
        }

        // @todo: Validate json against schema

        return self::fromObject($json);
    }


    public static function fromObject(\StdClass $data)
    {
        $result = [];

        // Iterate over each of the sections in the data
        $sectionsInData = [];
        foreach ($data->sections as $s) {
            $sectionsInData[] = $s->handle;
            $section = Models\Section::loadFromHandle($s->handle);

            // New Section
            if (!($section instanceof Models\Section)) {
                $result[] = (new Diff\Record)
                    ->op(Diff\Record::OP_REMOVED)
                    ->type(Diff\Record::TYPE_SECTION)
                    ->nameOriginal($s->handle)
                    ->context(json_encode($s, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))
                ;
            } else {

                foreach($section::getFieldMappings() as $name => $properties) {
                    $nameActual = $properties['name'];
                    if(!isset($s->$nameActual)) {
                        // This will only really trigger for ID, so we can skip it.
                        continue;
                    } elseif($section->$nameActual->value != $s->$nameActual) {

                        $valueOriginal = $s->$nameActual;
                        $valueNew = $section->$nameActual->value;

                        if(AbstractTableModel::isFlagSet($properties['flags'], AbstractTableModel::FLAG_BOOL)) {
                            $valueOriginal = $valueOriginal == true ? "true" : "false";
                            $valueNew = $valueNew == true ? "true" : "false";
                        }

                        $result[] = (new Diff\Record)
                            ->op(Diff\Record::OP_UPDATED)
                            ->type(Diff\Record::TYPE_SECTION)
                            ->nameOriginal("{$section->handle}->$nameActual")
                            ->valueNew($valueNew)
                            ->valueOriginal($valueOriginal)
                            ->context(json_encode($s, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))
                        ;
                    }
                }

                // Now go over all the fields
                $fieldsInComparisonData = [];
                foreach ($s->fields as $f) {
                    $fieldsInComparisonData[] = $f->elementName;

                    try{
                        $field = AbstractField::loadFromElementName($f->elementName);

                        foreach($field::getFieldMappings() as $name => $properties) {
                            $nameActual = $properties['name'];

                            $comparisonValueActual = null;
                            if((!isset($f->$nameActual) && !isset($f->custom->$nameActual)) || in_array($nameActual, ['sortOrder', 'location', 'showColumn'])) {
                                continue;
                            } elseif(isset($f->$nameActual)) {
                                $comparisonValueActual = $f->$nameActual;
                            } else {
                                $comparisonValueActual = $f->custom->$nameActual;
                            }

                            if(is_object($comparisonValueActual)) {
                                continue;
                            }

                            if($field->$nameActual->value != $comparisonValueActual) {

                                $valueOriginal = $comparisonValueActual;
                                $valueNew = $field->$nameActual->value;

                                if(AbstractTableModel::isFlagSet($properties['flags'], AbstractTableModel::FLAG_BOOL)) {
                                    $valueOriginal = $valueOriginal == true ? "true" : "false";
                                    $valueNew = $valueNew == true ? "true" : "false";
                                }

                                $result[] = (new Diff\Record)
                                    ->op(Diff\Record::OP_UPDATED)
                                    ->type(Diff\Record::TYPE_FIELD)
                                    ->nameOriginal("{$section->handle}::{$field->elementName}->$nameActual")
                                    ->valueNew($valueNew)
                                    ->valueOriginal($valueOriginal)
                                    ->context(json_encode($f, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))
                                ;
                            }
                        }

                    } catch (Exceptions\NoSuchFieldException $ex) {
                        $result[] = (new Diff\Record)
                            ->op(Diff\Record::OP_REMOVED)
                            ->type(Diff\Record::TYPE_FIELD)
                            ->nameOriginal("{$section->handle}->{$f->elementName}")
                            ->context(json_encode($f, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES))
                        ;
                    }

                }

                // Iterate over each field in the section and look for any
                // that do not appear in the comparison data. These are fields that
                // have been added
                foreach($section->fields() as $f){
                    if(!in_array($f->elementName, $fieldsInComparisonData)) {
                        $result[] = (new Diff\Record)
                            ->op(Diff\Record::OP_ADDED)
                            ->type(Diff\Record::TYPE_FIELD)
                            ->nameNew("{$section->handle}->{$f->elementName}")
                            ->context((string)$f)
                        ;
                    }
                }
            }
        }

        // Iterate over each section in the database and look for any
        // that do not appear in the data. These are sectons that have been
        // added
        foreach(Models\Section::all() as $s){
            if(!in_array($s->handle, $sectionsInData)) {
                $result[] = (new Diff\Record)
                    ->op(Diff\Record::OP_ADDED)
                    ->type(Diff\Record::TYPE_SECTION)
                    ->nameNew($s->handle)
                    ->context((string)$s)
                ;
            }
        }

        return $result;
    }
}
