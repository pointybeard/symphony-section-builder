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

class Primaryentry extends AbstractField implements FieldInterface
{
    const TYPE = 'primaryentry';
    const TABLE = 'tbl_fields_primaryentry';

    public static function getFieldMappings(): \stdClass
    {
        return (object) array_merge((array) parent::getFieldMappings(), [
            'default_state' => [
                'name' => 'defaultState',
                'flags' => self::FLAG_STR,
            ],

            'auto_toggle' => [
                'name' => 'autoToggle',
                'flags' => self::FLAG_BOOL,
            ],
        ]);
    }

    public function getEntriesDataCreateTableSyntax(): string
    {
        return sprintf(
            "CREATE TABLE IF NOT EXISTS `tbl_entries_data_%d` (
              `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
              `entry_id` int(11) unsigned NOT NULL,
              `value` enum('yes','no') COLLATE utf8_unicode_ci NOT NULL DEFAULT 'no',
              PRIMARY KEY (`id`),
              UNIQUE KEY `entry_id` (`entry_id`),
              KEY `value` (`value`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;",
            (int) $this->id->value
        );
    }

    protected static function stringToEnumOnOff(string $string): string
    {
        return 'on' == strtolower($string) ? 'on' : 'off';
    }

    protected static function boolToEnumYesNo(bool $value): string
    {
        return true == $value ? 'yes' : 'no';
    }

    public function getDatabaseReadyData(): array
    {
        return [
            'field_id' => (int) $this->id->value,
            'default_state' => self::stringToEnumOnOff((string) $this->defaultState),
            'auto_toggle' => self::boolToEnumYesNo($this->autoToggle->value),
        ];
    }
}
