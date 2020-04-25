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

namespace pointybeard\Symphony\SectionBuilder\Actions;

use pointybeard\Helpers\Cli;
use pointybeard\Helpers\Functions;
use pointybeard\Symphony\SectionBuilder;

class Import extends SectionBuilder\AbstractAction
{
    public function addActionInputTypesToCollection(Cli\Input\InputCollection $collection): Cli\Input\InputCollection
    {
        return $collection
            ->add(
                Cli\Input\InputTypeFactory::build('LongOption')
                    ->name('json')
                    ->short('j')
                    ->flags(Cli\Input\AbstractInputType::FLAG_REQUIRED | Cli\Input\AbstractInputType::FLAG_VALUE_REQUIRED)
                    ->description('Path to the input JSON data')
                    ->validator(function (Cli\Input\AbstractInputType $input, Cli\Input\AbstractInputHandler $context) {
                        // Make sure -j (--json) is a valid file that can be read
                        if (false === Functions\Json\json_validate_file($context->find('json'), $code, $message)) {
                            throw new SectionBuilder\Exceptions\SectionBuilderException(sprintf('The file specified via option --json does not exist or is an invalid JSON document. Returned: %s', $message));
                        }

                        return $context->find('json');
                    })
            )
        ;
    }

    public function execute(Cli\Input\AbstractInputHandler $argv): int
    {
        try {
            SymphonyPDO\Loader::instance((object) $databaseCredentials);
            SectionBuilder\Import::fromJsonFile($argv->find('json'));
        } catch (Exception $ex) {
            SectionBuilder\Includes\Functions\output('Unable import data. Returned: '.$ex->getMessage(), SectionBuilder\Includes\Functions\OUTPUT_ERROR);

            return SectionBuilder\Application::RETURN_WITH_ERRORS;
        }

        return SectionBuilder\Application::RETURN_SUCCESS;
    }
}
