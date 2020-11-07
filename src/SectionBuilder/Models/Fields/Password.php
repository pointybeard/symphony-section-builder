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

namespace pointybeard\Symphony\SectionBuilder\Models\Fields;

use pointybeard\Symphony\SectionBuilder\AbstractField;
use pointybeard\Symphony\SectionBuilder\Interfaces\FieldInterface;

class Password extends AbstractField implements FieldInterface
{
    const TYPE = 'password';
    const TABLE = 'tbl_fields_password';

    public static function getFieldMappings(): \stdClass
    {
        return (object) array_merge((array) parent::getFieldMappings(), [
            'length' => [
                'name' => 'length',
                'flags' => self::FLAG_INT,
            ],

            'strength' => [
                'name' => 'strength',
                'flags' => self::FLAG_STR,
            ],
        ]);
    }

    public function getEntriesDataCreateTableSyntax(): string
    {
        return sprintf("
            CREATE TABLE IF NOT EXISTS `tbl_entries_data_%d` (
              `id` int(11) unsigned NOT NULL auto_increment,
              `entry_id` int(11) unsigned NOT NULL,
              `password` varchar(150) default NULL,
              `length` tinyint(2) NOT NULL,
              `strength` enum('weak', 'good', 'strong') NOT NULL,
              PRIMARY KEY  (`id`),
              KEY `entry_id` (`entry_id`),
              KEY `length` (`length`),
              KEY `password` (`password`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ", (int) $this->id->value);
    }

    public function getDatabaseReadyData(): array
    {
        return [
            'field_id' => (int) $this->id->value,
            'length' => (int) $this->length->value,
            'strength' => (string) $this->strength,
        ];
    }
}
