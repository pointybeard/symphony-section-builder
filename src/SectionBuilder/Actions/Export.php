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
                    ->description('save JSON export to this location')
                    ->default(null)
            )
            ->add(
                Cli\Input\InputTypeFactory::build('LongOption')
                    ->name('less')
                    ->flags(Cli\Input\AbstractInputType::FLAG_OPTIONAL)
                    ->description('reduces the amount of meta data included in the export e.g. creation dates')
                    ->default(false)
            )
            ->add(
                Cli\Input\InputTypeFactory::build('LongOption')
                    ->name('exclude')
                    ->flags(Cli\Input\AbstractInputType::FLAG_OPTIONAL | Cli\Input\AbstractInputType::FLAG_VALUE_REQUIRED)
                    ->description('comma delimited list of sections to skip.')
                    ->validator(function (Cli\Input\AbstractInputType $input, Cli\Input\AbstractInputHandler $context) {
                        $excludedSections = explode(",", (string)$context->find('exclude'));
                        $excludedSections = array_map("trim", $excludedSections);
                        return $excludedSections;
                    })
                    ->default([])
            )
            ->add(
                Cli\Input\InputTypeFactory::build('LongOption')
                    ->name('limit')
                    ->flags(Cli\Input\AbstractInputType::FLAG_OPTIONAL | Cli\Input\AbstractInputType::FLAG_VALUE_REQUIRED)
                    ->description('comma delimited list of sections to limit export to.')
                    ->validator(function (Cli\Input\AbstractInputType $input, Cli\Input\AbstractInputHandler $context) {
                        $limitToSections = explode(",", (string)$context->find('limit'));
                        $limitToSections = array_map("trim", $limitToSections);
                        return $limitToSections;
                    })
                    ->default([])
            )
        ;
    }

    public function execute(Cli\Input\AbstractInputHandler $argv): int
    {
        $excludedSections = $argv->find('exclude');
        $limitToSections = $argv->find('limit');

        try {
            $output = ['sections' => []];

            foreach (SectionBuilder\Models\Section::all() as $section) {

                if(true == in_array((string)$section->handle(), $excludedSections)) {
                    echo Colour::colourise('Excluding section '.(string)$section->handle(), Colour::FG_YELLOW).PHP_EOL;
                    continue;
                }

                if(false == empty($limitToSections) && false == in_array((string)$section->handle(), $limitToSections)) {
                    echo Colour::colourise('Skipping section '.(string)$section->handle(), Colour::FG_YELLOW).PHP_EOL;
                    continue;
                }

                $output['sections'][] = json_decode(
                    $section->__toJson(
                        true === $argv->find('less') 
                            ? SectionBuilder\Models\Section::FLAG_LESS 
                            : SectionBuilder\Models\Section::FLAG_EXCLUDE_IDS
                    ),
                    false
                );
            }

            // #1 - Now that we have the sections, lets sort them based on dependency.
            try {
                $output['sections'] = SectionBuilder\AbstractOperation::orderSectionsByAssociations(
                    $output['sections']
                );
            } catch(SectionBuilder\Exceptions\SectionBuilderException $ex) {
                echo Colour::colourise('WARNING: Unable to order sections due to circular dependency warning. You might need to re-order sections in the output manally.', Colour::FG_RED).PHP_EOL;
            }

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
