<?php
namespace pointybeard\Symphony\SectionBuilder\Lib\Models\Fields;

use pointybeard\Symphony\SectionBuilder\Lib\AbstractField;
use pointybeard\Symphony\SectionBuilder\Lib\Interfaces\FieldInterface;
use pointybeard\PropertyBag\Lib;

class Number extends AbstractField implements FieldInterface
{
    const TYPE = "number";
    const TABLE = "tbl_fields_number";

    public function getDatabaseReadyData()
    {
        return [
            'field_id' => (int)$this->id->value,
        ];
    }

    protected function installEntriesDataTable()
    {
        $sql = sprintf(
            "CREATE TABLE IF NOT EXISTS `tbl_entries_data_%d` (
                `id` int(11) unsigned NOT NULL auto_increment,
                `entry_id` int(11) unsigned NOT NULL,
                `value` double default NULL,
                PRIMARY KEY  (`id`),
                UNIQUE KEY `entry_id` (`entry_id`),
                KEY `value` (`value`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;",
            (int)$this->id->value
        );
        \SymphonyPDO\Loader::instance()->exec($sql);
        return true;
    }
}
