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

namespace pointybeard\Symphony\SectionBuilder\Interfaces;

use pointybeard\Helpers\Cli\Input;

interface ActionInterface
{
    public function addActionInputTypesToCollection(Input\InputCollection $collection): Input\InputCollection;

    public function execute(Input\AbstractInputHandler $argv): int;
}
