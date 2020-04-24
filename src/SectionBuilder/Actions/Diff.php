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
use pointybeard\Helpers\Functions;
use pointybeard\Symphony\SectionBuilder;
use pointybeard\Symphony\SectionBuilder\Models;

class Diff extends SectionBuilder\AbstractAction
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

    public function execute(Cli\Input\AbstractInputHandler $argv): bool
    {
        try {
            $count = (object) [
                Models\Diff\Record::OP_ADDED => 0,
                Models\Diff\Record::OP_REMOVED => 0,
                Models\Diff\Record::OP_UPDATED => 0,
                'total' => 0,
            ];

            foreach (SectionBuilder\Diff::fromJsonFile($argv->find('json')) as $d) {
                ++$count->total;
                ++$count->{$d->op};

                switch ($d->op) {
                    case Models\Diff\Record::OP_ADDED:
                        echo Colour::colourise(sprintf(
                            'ADDED %s - %s', $d->type, $d->nameNew
                        ), Colour::FG_GREEN).PHP_EOL;
                        break;

                    case Models\Diff\Record::OP_REMOVED:
                        echo Colour::colourise(sprintf(
                            'REMOVED %s - %s', $d->type, $d->nameOriginal
                        ), Colour::FG_RED).PHP_EOL;
                        break;

                    case Models\Diff\Record::OP_UPDATED:
                        echo Colour::colourise(sprintf(
                            "UPDATED %s - %s changed from '%s' to '%s'",
                            $d->type,
                            $d->nameOriginal,
                            $d->valueOriginal,
                            $d->valueNew
                        ), Colour::FG_YELLOW).PHP_EOL;
                        break;
                }
            }

            (new Cli\Message\Message())
                ->message(sprintf(
                    'Completed (%d total, %d added, %d updated, %d removed)',
                    $count->total,
                    $count->{Models\Diff\Record::OP_ADDED},
                    $count->{Models\Diff\Record::OP_UPDATED},
                    $count->{Models\Diff\Record::OP_REMOVED}
                ))
                ->foreground(Colour::FG_GREEN)
                ->display()
            ;
        } catch (\Exception $ex) {
            SectionBuilder\Includes\Functions\output('A problem was encountered. Returned: '.$ex->getMessage(), SectionBuilder\Includes\Functions\OUTPUT_ERROR);
        }

        return true;
    }
}
