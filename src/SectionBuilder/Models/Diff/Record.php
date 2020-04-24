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

namespace pointybeard\Symphony\SectionBuilder\Models\Diff;

class Record
{
    public const OP_ADDED = 'added';
    public const OP_REMOVED = 'removed';
    public const OP_RENAMED = 'renamed';
    public const OP_UPDATED = 'updated';

    public const TYPE_FIELD = 'Field';
    public const TYPE_SECTION = 'Section';

    private $op;
    private $type;
    private $context;
    private $nameOriginal;
    private $nameNew;
    private $valueOriginal;
    private $valueNew;

    public function __get($name)
    {
        return $this->$name;
    }

    public function __set($name, $value)
    {
        $this->$name = $value;

        return true;
    }

    public function __call($name, $args)
    {
        if (empty($args)) {
            return $this->$name;
        }

        $this->$name = $args[0];

        return $this;
    }
}
