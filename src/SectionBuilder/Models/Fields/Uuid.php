<?php

declare(strict_types=1);

namespace pointybeard\Symphony\SectionBuilder\SectionBuilder\Models\Fields;

use pointybeard\Symphony\SectionBuilder\SectionBuilder\AbstractField;
use pointybeard\Symphony\SectionBuilder\SectionBuilder\Interfaces\FieldInterface;

class Uuid extends AbstractField implements FieldInterface
{
    const TYPE = 'uuid';
    const TABLE = 'tbl_fields_uuid';

    public function getDatabaseReadyData(): array
    {
        return [
            'field_id' => (int) $this->id->value,
        ];
    }

    public function getEntriesDataCreateTableSyntax(): string
    {
        return sprintf(
            'CREATE TABLE IF NOT EXISTS `tbl_entries_data_%d` (
                `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
                `entry_id` int(11) unsigned NOT NULL,
                `value` varchar(36) COLLATE utf8_unicode_ci DEFAULT NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `entry_id` (`entry_id`),
                KEY `value` (`value`)
              ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;',
            (int) $this->id->value
        );
    }
}
