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

namespace pointybeard\Symphony\SectionBuilder\Includes\Functions;

use pointybeard\Helpers\Cli\Colour\Colour;
use pointybeard\Helpers\Cli\Message\Message;
use pointybeard\Helpers\Cli\Prompt\Prompt;
use pointybeard\Helpers\Functions\Cli;
use pointybeard\Symphony\SectionBuilder\Application;

const OUTPUT_HEADING = 1;
const OUTPUT_ERROR = 2;
const OUTPUT_WARNING = 3;
const OUTPUT_NOTICE = 4;
const OUTPUT_INFO = 5;
const OUTPUT_SUCCESS = 6;

const FLAGS_YES = 0x020;
const FLAGS_NO = 0x040;
const FLAGS_SKIP = 0x080;

if (!function_exists(__NAMESPACE__.'\output')) {
    function output(string $message, ?int $type = OUTPUT_INFO, ?int $flags = Message::FLAG_APPEND_NEWLINE): void
    {
        $output = (new Message())
            ->message($message)
            ->flags($flags)
            ->foreground(Colour::FG_DEFAULT)
            ->background(Colour::BG_DEFAULT)
        ;

        switch ($type) {
            case OUTPUT_ERROR:
                Cli\display_error_and_exit($message, 'CRITICAL ERROR!');
                break;

            case OUTPUT_WARNING:
                $output
                    ->message("WARNING! {$message}")
                    ->foreground(Colour::FG_RED)
                ;
                break;

            case OUTPUT_NOTICE:
                $output->foreground(Colour::FG_YELLOW);
                break;

            case OUTPUT_SUCCESS:
                $output->foreground(Colour::FG_GREEN);
                break;

            case OUTPUT_HEADING:
                $output
                    ->foreground(Colour::FG_WHITE)
                    ->background(Colour::BG_BLUE)
                ;
                break;

            default:
            case OUTPUT_INFO:
                break;
        }

        $output->display();
    }
}

if (!function_exists(__NAMESPACE__.'\ask_to_proceed')) {
    function ask_to_proceed(?int $flags = null, string $prompt = 'Continue anyway %s?', string $affirmative = '@^y(es)?$@i', string $negative = '@^no?$@i'): int
    {
        while (true) {
            $proceed = (new Prompt(sprintf($prompt, '(y=yes, n=no)')))
                ->display()
            ;

            if (preg_match($negative, $proceed)) {
                output('Execution termined by user', OUTPUT_NOTICE);
                exit(Application::RETURN_SUCCESS);
            } elseif (preg_match($affirmative, $proceed)) {
                return FLAGS_YES;
            }

            output('Please enter a valid response', OUTPUT_WARNING);
        }
    }
}
