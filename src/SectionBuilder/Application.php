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

use pointybeard\Helpers\Cli;
use pointybeard\Helpers\Cli\Colour\Colour;
use pointybeard\Helpers\Foundation\Factory;
use pointybeard\Helpers\Functions;
use pointybeard\Helpers\Functions\Json;
use pointybeard\Symphony\SectionBuilder;

final class Application
{
    public const VERSION = '1.0.0';
    public const VERSION_ID = '10000';

    public const RETURN_SUCCESS = 0;
    public const RETURN_WITH_ERRORS = 1;
    public const RETURN_FAILED = 254;

    private $databaseOptionInputValidator = null;
    private $actionValidator = null;

    private function manpage(): \StdClass
    {
        return (object) [
            'name' => 'section-builder',
            'version' => self::VERSION,
            'description' => 'utility for automating the creation and updating of Symphony CMS sections and their fields',
            'examples' => [
                'bin/section-builder export -o /path/to/outputfile.json --manifest=/path/to/manifest',
                'bin/section-builder import -j /path/to/file.json --manifest=/path/to/manifest',
                'bin/section-builder diff -j /path/to/file.json --manifest=/path/to/manifest',
            ],
            'support' => 'If you believe you have found a bug, please report it using the GitHub issue tracker at https://github.com/pointybeard/symphony-section-builder/issues or fork the library and submit a fix via pull request.'.PHP_EOL.PHP_EOL.'Copyright 2018-2020 Alannah Kearney. Use -L to see software licence information.'.PHP_EOL,
        ];
    }

    public function __construct()
    {
        /*
        Set up a few basics
        */
        $this->databaseOptionInputValidator = function (Cli\Input\AbstractInputType $input, Cli\Input\AbstractInputHandler $context) {
            if (null !== $context->find('manifest')) {
                throw new Exceptions\SectionBuilderException('Does not make sense to specify both --manifest and --'.$input->name);
            }

            return $context->find($input->name);
        };

        $this->actionValidator = function (Cli\Input\AbstractInputType $input, Cli\Input\AbstractInputHandler $context) {
            if (false == class_exists(__NAMESPACE__.'\\ActionFactory')) {
                Factory\create(
                    __NAMESPACE__.'\\ActionFactory',
                    '\\pointybeard\\Symphony\\SectionBuilder\\Actions\\%s',
                    '\\pointybeard\\Symphony\\SectionBuilder\\AbstractAction'
                );
            }

            try {
                return ActionFactory::build(ucfirst($context->find('action')));
            } catch (Factory\Exceptions\UnableToInstanciateConcreteClassException $ex) {
                throw new \Exception('Invalid action specified. Returned: '.$ex->getMessage());
            }
        };
    }

    public function run(): int
    {
        /*
        Build the input collection
        */
        $collection = (new Cli\Input\InputCollection())
            ->add(
                Cli\Input\InputTypeFactory::build('LongOption')
                    ->name('manifest')
                    ->flags(Cli\Input\AbstractInputType::FLAG_OPTIONAL | Cli\Input\AbstractInputType::FLAG_VALUE_REQUIRED)
                    ->description('Path to the manifest folder containing a config.php or config.json file. This is an alternative to providing database credentials directly, instead reading them from the config.')
                    ->default(null)
                    ->validator(function (Cli\Input\AbstractInputType $input, Cli\Input\AbstractInputHandler $context) {
                        $config = null;
                        $manifest = null;

                        if (false == $manifest = realpath($context->find('manifest'))) {
                            throw new Exceptions\SectionBuilderException('Path provided by --manifest is invalid.');
                        }

                        if (true == is_readable($manifest.DIRECTORY_SEPARATOR.'config.php')) {
                            include $manifest.DIRECTORY_SEPARATOR.'config.php';
                        } elseif (true == file_exists($manifest.DIRECTORY_SEPARATOR.'config.json')) {
                            $config = $manifest.DIRECTORY_SEPARATOR.'config.json';

                            try {
                                $settings = Json\json_decode_file($config, true);
                            } catch (\JsonException $ex) {
                                throw new Exceptions\SectionBuilderException("Config file {$config} is not valid json. Returned: ".$ex->getMessage());
                            }
                        } else {
                            throw new Exceptions\SectionBuilderException('The path specified by --manifest does not contain a config.php or config.json file.');
                        }

                        if (!is_array($settings) || !isset($settings['database'])) {
                            throw new Exceptions\SectionBuilderException('Config file found in location specified by --manifest does not appear to be a valid Symphony CMS config file.');
                        }

                        return array_merge(
                            [
                                'host' => '127.0.0.1',
                                'port' => '3306',
                                'db' => null,
                                'user' => null,
                                'password' => null,
                                'tbl_prefix' => 'tbl_',
                            ],
                            $settings['database']
                        );
                    }),
                false,
                Cli\Input\InputCollection::POSITION_PREPEND
            )
            ->add(
                Cli\Input\InputTypeFactory::build('LongOption')
                    ->name('symphony')
                    ->flags(Cli\Input\AbstractInputType::FLAG_OPTIONAL | Cli\Input\AbstractInputType::FLAG_VALUE_REQUIRED)
                    ->description('Path to SymphonyCMS core.')
                    ->validator(function (Cli\Input\AbstractInputType $input, Cli\Input\AbstractInputHandler $context) {
                        if (class_exists('Symphony')) {
                            (new Cli\Message\Message())
                                ->message('WARNING - Symphony core has already been included via Composer. Ignoring --symphony.')
                                ->foreground(Colour::FG_YELLOW)
                                ->display()
                            ;

                            return;
                        } elseif (false === $path = realpath($context->find('symphony'))) {
                            throw new Exceptions\SectionBuilderException("The path {$path} specified by --symphony does not appear to be valid");
                        } elseif (!file_exists("{$path}/vendor/autoload.php")) {
                            throw new Exceptions\SectionBuilderException("No vendor autoload file could be located at the path {$path} specified by --symphony");
                        } else {
                            include "{$path}/vendor/autoload.php";
                        }

                        return;
                    })
                    ->default(__DIR__.'/../vendor/symphonycms/symphony-2'),
                false,
                Cli\Input\InputCollection::POSITION_PREPEND
            )
            ->add(
                Cli\Input\InputTypeFactory::build('Flag')
                    ->name('L')
                    ->flags(Cli\Input\AbstractInputType::FLAG_OPTIONAL)
                    ->description('View the software licence this script is released under'),
                false,
                Cli\Input\InputCollection::POSITION_PREPEND
            )
            ->add(
                Cli\Input\InputTypeFactory::build('LongOption')
                    ->name('help')
                    ->short('h')
                    ->flags(Cli\Input\AbstractInputType::FLAG_OPTIONAL)
                    ->description('print this help information'),
                false,
                Cli\Input\InputCollection::POSITION_PREPEND
            )
            ->add(
                Cli\Input\InputTypeFactory::build('LongOption')
                    ->name('database-name')
                    ->flags(Cli\Input\AbstractInputType::FLAG_OPTIONAL | Cli\Input\AbstractInputType::FLAG_VALUE_REQUIRED)
                    ->description('database to use.')
                    ->default(null)
                    ->validator($this->databaseOptionInputValidator)
            )
            ->add(
                Cli\Input\InputTypeFactory::build('LongOption')
                    ->name('database-user')
                    ->flags(Cli\Input\AbstractInputType::FLAG_OPTIONAL | Cli\Input\AbstractInputType::FLAG_VALUE_REQUIRED)
                    ->description('name of database user')
                    ->default(null)
                    ->validator($this->databaseOptionInputValidator)
            )
            ->add(
                Cli\Input\InputTypeFactory::build('LongOption')
                    ->name('database-pass')
                    ->flags(Cli\Input\AbstractInputType::FLAG_OPTIONAL | Cli\Input\AbstractInputType::FLAG_VALUE_OPTIONAL)
                    ->description('password for project database user. Will prompt if omited')
                    ->validator(function (Cli\Input\AbstractInputType $input, Cli\Input\AbstractInputHandler $context) {
                        if (null !== $context->find('manifest')) {
                            throw new Exceptions\SectionBuilderException('Does not make sense to specify both --manifest and --'.$input->name);
                        }
                        if (false == is_bool($context->find('database-pass'))) {
                            return $context->find('database-pass');
                        }

                        return (new Cli\Prompt\Prompt('Enter Password'))
                            ->flags(Cli\Prompt\Prompt::FLAG_SILENT)
                            ->display()
                        ;
                    })
                    ->default(null)
            )
            ->add(
                Cli\Input\InputTypeFactory::build('LongOption')
                    ->name('database-table-prefix')
                    ->flags(Cli\Input\AbstractInputType::FLAG_OPTIONAL | Cli\Input\AbstractInputType::FLAG_VALUE_REQUIRED)
                    ->description('the prefix for all tables in this project. Default is tbl_')
                    ->default('tbl_')
                    ->validator($this->databaseOptionInputValidator)
            )
            ->add(
                Cli\Input\InputTypeFactory::build('LongOption')
                    ->name('database-host')
                    ->flags(Cli\Input\AbstractInputType::FLAG_OPTIONAL | Cli\Input\AbstractInputType::FLAG_VALUE_REQUIRED)
                    ->description('host IP of database to use for deployment. Default is 127.0.0.1')
                    ->default('127.0.0.1')
                    ->validator($this->databaseOptionInputValidator)
            )
            ->add(
                Cli\Input\InputTypeFactory::build('LongOption')
                    ->name('database-port')
                    ->flags(Cli\Input\AbstractInputType::FLAG_OPTIONAL | Cli\Input\AbstractInputType::FLAG_VALUE_REQUIRED)
                    ->description('port number of database server. Default is 3306')
                    ->default('3306')
                    ->validator($this->databaseOptionInputValidator)
            )
            ->add(
                Cli\Input\InputTypeFactory::build('Argument')
                    ->name('action')
                    ->flags(Cli\Input\AbstractInputType::FLAG_REQUIRED)
                    ->description('The name of the action to perform. Available actions are: import, export, and diff.')
                    ->validator(new Cli\Input\Validator($this->actionValidator))
            )
        ;

        try {
            // Very important to skip both unrecognised input and also vaidation
            $argv = Cli\Input\InputHandlerFactory::build(
                'Argv',
                $collection,
                Cli\Input\AbstractInputHandler::FLAG_VALIDATION_SKIP_UNRECOGNISED | Cli\Input\AbstractInputHandler::FLAG_BIND_SKIP_VALIDATION
            );
        } catch (\Exception $ex) {
            SectionBuilder\Includes\Functions\output('An error occurred while attempting to process arguments and options. Returned: '.$ex->getMessage(), SectionBuilder\Includes\Functions\OUTPUT_ERROR);

            return self::RETURN_FAILED;
        }

        /*
        Add action specific inputs. Note, no validation has been carried out at this stage.
        */
        if (null !== $argv->find('action')) {
            // Since validation has not been performed yet, lets do it ourself by invoking $this->actionValidator but ignoring errors
            // as this is really only for the sake of the help page
            try {
                $result = ($this->actionValidator)($collection->find('action'), $argv);
                $collection = $result->addActionInputTypesToCollection($collection);
            } catch (\Exception $ex) {
                // Don't worry about it. The input will be validated further down so any
                // issues with the specified action, e.g. doesnt exist will, be caught
                // there instead
            }
        }

        /*
        Display the help screen if requested
        */
        if (true === $argv->find('h')) {
            echo Functions\Cli\manpage(
                $this->manpage()->name,
                $this->manpage()->version,
                $this->manpage()->description,
                $collection,
                Colour::FG_GREEN,
                Colour::FG_WHITE,
                [
                    'Examples' => implode(PHP_EOL, $this->manpage()->examples),
                    'Support' => $this->manpage()->support,
                ]
            );

            return self::RETURN_SUCCESS;
        }

        /*
        Display the licence screen if requested
        */
        if (true === $argv->find('L')) {
            echo file_get_contents(__DIR__.'/../../LICENCE').PHP_EOL;

            return self::RETURN_SUCCESS;
        }

        /*
        Bind the collection in order to perform input validation
        */
        try {
            $argv = Cli\Input\InputHandlerFactory::build('Argv', $collection);
            $argv->bind($collection);

            if (null === $argv->find('manifest') && (null === $argv->find('database-name') || null === $argv->find('database-user'))) {
                SectionBuilder\Includes\Functions\output('Insufficent database credentials supplied. You must specify either --manifest or both --database-name & --database-user at a minimum', SectionBuilder\Includes\Functions\OUTPUT_ERROR);
            }
        } catch (Cli\Input\Exceptions\RequiredInputMissingException | Cli\Input\Exceptions\UnrecognisedInputException | Cli\Input\Exceptions\RequiredInputMissingValueException $ex) {
            fwrite(
                STDERR,
                sprintf(
                    '%s%s%s%2$s%2$sTry --help for more options.%2$s',
                    Colour::colourise($this->manpage()->name.": {$ex->getMessage()}", Colour::FG_RED),
                    PHP_EOL,
                    Functions\Cli\usage($this->manpage()->name, $collection)
                )
            );

            return self::RETURN_FAILED;
        } catch (Cli\Input\Exceptions\InputValidationFailedException $ex) {
            SectionBuilder\Includes\Functions\output($ex->getMessage(), SectionBuilder\Includes\Functions\OUTPUT_ERROR);
        } catch (\Exception $ex) {
            SectionBuilder\Includes\Functions\output('An error occurred while attempting to process arguments and options. Returned: '.$ex->getMessage(), SectionBuilder\Includes\Functions\OUTPUT_ERROR);

            return self::RETURN_FAILED;
        }

        /*
        Build the credentials used for connecting to the database
        */
        if (null === $argv->find('manifest')) {
            $databaseCredentials = [
                'host' => $argv->find('database-host'),
                'port' => $argv->find('database-port'),
                'db' => $argv->find('database-name'),
                'user' => $argv->find('database-user'),
                'password' => $argv->find('database-pass'),
                'tbl_prefix' => $argv->find('database-table-prefix'),
            ];
        } else {
            $databaseCredentials = $argv->find('manifest');
        }

        /*
        Check the database connection
        */
        try {
            \SymphonyPDO\Loader::instance((object) $databaseCredentials);
        } catch (\Exception $ex) {
            SectionBuilder\Includes\Functions\output('Unable to connect with the database credentials provided. Returned:'.$ex->getMessage(), SectionBuilder\Includes\Functions\OUTPUT_ERROR);

            return self::RETURN_FAILED;
        }

        /*
        Bootstrap is complete; now run the action
        */
        return $action->execute($argv);
    }
}
