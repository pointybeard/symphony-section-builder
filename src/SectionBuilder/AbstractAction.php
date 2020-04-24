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

use pointybeard\Helpers\Cli\Input;

abstract class AbstractAction implements Interfaces\ActionInterface
{
    public function addActionInputTypesToCollection(Input\InputCollection $collection): Input\InputCollection
    {
        return $collection;
    }
}
