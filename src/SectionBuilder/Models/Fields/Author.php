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

class Author extends AbstractField implements FieldInterface
{
    const TYPE = 'author';
    const TABLE = 'tbl_fields_author';

    public static function getFieldMappings(): \stdClass
    {
        return (object) array_merge((array) parent::getFieldMappings(), [
            'allow_multiple_selection' => [
                'name' => 'allowMultipleSelection',
                'flags' => self::FLAG_BOOL,
            ],

            'default_to_current_user' => [
                'name' => 'defaultToCurrentUser',
                'flags' => self::FLAG_BOOL,
            ],

            'author_types' => [
                'name' => 'authorTypes',
                'flags' => self::FLAG_STR,
            ],
        ]);
    }

    public function getEntriesDataCreateTableSyntax(): string
    {
        return sprintf(
            'CREATE TABLE IF NOT EXISTS `tbl_entries_data_%d` (
              `id` int(11) unsigned NOT null auto_increment,
              `entry_id` int(11) unsigned NOT null,
              `author_id` int(11) unsigned null,
              PRIMARY KEY  (`id`),
              UNIQUE KEY `author` (`entry_id`, `author_id`),
              KEY `author_id` (`author_id`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;',
            (int) $this->id->value
        );
    }

    protected static function boolToEnumYesNo(string $value): bool
    {
        return true == $value ? 'yes' : 'no';
    }

    public function getDatabaseReadyData(): array
    {
        return [
            'field_id' => (int) $this->id->value,
            'author_types' => (string) $this->authorTypes,
            'allow_multiple_selection' => self::boolToEnumYesNo($this->allowMultipleSelection->value),
            'default_to_current_user' => self::boolToEnumYesNo($this->defaultToCurrentUser->value),
        ];
    }
}
