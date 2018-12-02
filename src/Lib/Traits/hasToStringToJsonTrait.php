<?php
namespace pointybeard\Symphony\SectionBuilder\Lib\Traits;

use pointybeard\Symphony\SectionBuilder\Lib;

trait hasToStringToJsonTrait
{

    protected function removeIdFromArray(array $data) {
        unset($data['id']);
        unset($data['sectionId']);
        foreach($data as $name => $properties) {
            if(is_array($properties)) {
                $data[$name] = self::removeIdFromArray($properties);
            }
        }

        return $data;
    }

    public function __toJson($excludeIds = true) {
        $data = $this->__toArray();

        // Lets ignore ID and SectionId
        if($excludeIds) {
            $data = self::removeIdFromArray($data);
        }

        return json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    public function __toString() {
        return $this->__toJson();
    }
}