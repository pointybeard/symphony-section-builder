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
use pointybeard\Helpers\Cli\Colour\Colour;
use pointybeard\Symphony\SectionBuilder;

class Export extends SectionBuilder\AbstractAction
{
    public function addActionInputTypesToCollection(Cli\Input\InputCollection $collection): Cli\Input\InputCollection
    {
        return $collection
            ->add(
                Cli\Input\InputTypeFactory::build('LongOption')
                    ->name('output')
                    ->short('o')
                    ->flags(Cli\Input\AbstractInputType::FLAG_OPTIONAL | Cli\Input\AbstractInputType::FLAG_VALUE_REQUIRED)
                    ->description('Save JSON export to this location')
                    ->default(null)
            )
        ;
    }

    public function execute(Cli\Input\AbstractInputHandler $argv): int
    {
        try {
            \SymphonyPDO\Loader::instance((object) $databaseCredentials);
            $output = ['sections' => []];
            foreach (SectionBuilder\Models\Section::all() as $section) {
                $output['sections'][] = json_decode((string) $section, false);
            }

            // #1 - Now that we have the sections, lets sort them based on dependency.
            $output['sections'] = SectionBuilder\AbstractOperation::orderSectionsByAssociations(
                $output['sections']
            );

            $json = json_encode(
                $output,
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
            );

            if (null === $argv->find('output')) {
                echo $json.PHP_EOL;
            } else {
                $file = $argv->find('output');
                file_put_contents($file, $json);
                echo Colour::colourise(filesize($file).' bytes written to '.$file, Colour::FG_GREEN).PHP_EOL;
            }
        } catch (Exception $ex) {
            SectionBuilder\Includes\Functions\output('Unable export data. Returned: '.$ex->getMessage(), SectionBuilder\Includes\Functions\OUTPUT_ERROR);

            return SectionBuilder\Application::RETURN_WITH_ERRORS;
        }

        return SectionBuilder\Application::RETURN_SUCCESS;
    }
}
