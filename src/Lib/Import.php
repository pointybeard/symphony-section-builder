<?php
namespace pointybeard\Symphony\SectionBuilder\Lib;

class Import
{

    protected static function hasAssociations(\StdClass $section) {
        return (isset($section->associations) && count($section->associations) > 0);
    }

    protected static function orderSectionsByAssociations(array $sections) {
        $associations = [];
        foreach($sections as $s) {
            $associations[$s->handle] = [];
            if(self::hasAssociations($s)) {
                foreach($s->associations as $a) {
                    $associations[$s->handle][] = $a->parent->section;
                }
            }
        }

        $associationsOrdered = [];
        while(!empty($associations)) {
            foreach($associations as $handle => $a) {
                // Iterate over each item in $a and see if they are all in
                // $associationsOrdered
                $allFound = true;
                if(!empty($a)) {
                    foreach($a as $h) {
                        if(!isset($associationsOrdered[$h])) {
                            $allFound = false;
                            break;
                        }
                    }
                }

                if($allFound == true) {
                    $associationsOrdered[$handle] = $a;
                    unset($associations[$handle]);
                }
            }
        }

        // Iterate over the, now ordered, list of sections to rebuild the
        // sections array
        $sectionsOrdered = [];
        foreach(array_keys($associationsOrdered) as $handle) {
            foreach($sections as $s) {
                if($s->handle == $handle){
                    $sectionsOrdered[] = $s;
                    continue 2;
                }
            }
        }

        return $sectionsOrdered;
    }

    public static function fromJsonFile($file){
        if (!is_readable($file)) {
            throw new \Exception(sprintf(
                "The file '%s' is not readable.",
                $file
            ));
        }
        return self::fromJsonString(file_get_contents($file));
    }

    public static function fromJsonString($string){
        $json = json_decode($string);
        if ($json == false || is_null($json)) {
            throw new \Exception(
                "String is not a valid JSON document."
            );
        }

        // @todo: Validate json against schema

        $sections = self::orderSectionsByAssociations($json->sections);

        $result = [];

        foreach($sections as $s) {
            $section = Models\Section::loadFromHandle($s->handle);
            if(!($section instanceof Models\Section)) {

                $section = (new Models\Section)
                    ->name($s->name)
                    ->handle($s->handle)
                    ->sortOrder($s->sortOrder)
                    ->hideFromBackendNavigation($s->hideFromBackendNavigation)
                    ->allowFiltering($s->allowFiltering)
                    ->navigationGroup($s->navigationGroup)
                    ->authorId($s->authorId)
                    ->modificationAuthorId($s->modificationAuthorId)
                    ->dateCreatedAt(date('c', strtotime($s->dateCreatedAt)))
                    ->dateCreatedAtGMT(gmdate('c', strtotime($s->dateCreatedAtGMT)))
                    ->dateModifiedAt(date('c', strtotime($s->dateModifiedAt)))
                    ->dateModifiedAtGMT(gmdate('c', strtotime($s->dateModifiedAtGMT)))
                ;

                foreach($s->fields as $f) {

                    $class = AbstractField::fieldTypeToClassName($f->type);

                    $field = (new $class)
                        ->label($f->label)
                        ->elementName($f->elementName)
                        ->location($f->location)
                        ->required($f->required)
                        ->showColumn($f->showColumn)
                    ;

                    foreach((array)$f->custom as $key => $value) {

                        // Look to see if this custom item is a reference to
                        // section and field
                        if($value instanceof \StdClass) {
                            if(!isset($value->section) || !isset($value->field)) {
                                // Hmm, we cannot handle this yet. Throw an exception
                                throw new \Exception("Unable to handle field of type {$f->type}. It contains non-standard objects in the custom values object.");
                            }

                            $relatedSection = Models\Section::loadFromHandle($value->section);
                            $relatedSection->fields();
                            if(!($relatedSection instanceof Models\Section)) {
                                var_dump($value);
                                die("why you do this section!!!!");
                            }
                            $relatedField = $relatedSection->findFieldByElementName($value->field);
                            if(!($relatedField instanceof AbstractField)) {
                                var_dump($value);
                                die("why you do this field!!!!");
                            }

                            $value = $relatedField;

                        }

                        $field->$key($value);
                    }

                    $section->addField($field);
                }

                $section->commit();
                $result[] = $section;
            }
        }

        return $result;
    }
}
