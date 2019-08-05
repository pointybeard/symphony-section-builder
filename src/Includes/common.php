<?php

declare(strict_types=1);

namespace pointybeard\Symphony\SectionBuilder\Common;

use pointybeard\Helpers\Cli;
use pointybeard\Helpers\Functions;
use pointybeard\Helpers\Cli\Colour\Colour;
use pointybeard\Helpers\Functions\Json;
use pointybeard\Symphony\SectionBuilder\Exceptions;

$databaseCredentials = [
    'host' => '127.0.0.1',
    'port' => '3306',
    'db' => null,
    'user' => null,
    'password' => null,
    'tbl_prefix' => 'tbl_',
];

$databaseOptionValidator = function (Cli\Input\AbstractInputType $input, Cli\Input\AbstractInputHandler $context) {
    if (null !== $context->find('manifest')) {
        throw new Exceptions\SectionBuilderException('Does not make sense to specify both --manifest and --'.$input->name);
    }
    return $context->find($input->name);
};

$collection
    ->add(
        Cli\Input\InputTypeFactory::build('LongOption')
            ->name('manifest')
            ->flags(Cli\Input\AbstractInputType::FLAG_OPTIONAL | Cli\Input\AbstractInputType::FLAG_VALUE_REQUIRED)
            ->description('Path to the manifest folder containing a config.php or config.json file. This is an alternative to providing database credentials directly, instead reading them from the config.')
            ->default(null)
            ->validator(function (Cli\Input\AbstractInputType $input, Cli\Input\AbstractInputHandler $context) use ($databaseCredentials) {

                $config = null;
                $manifest = null;

                if(false == $manifest = realpath($context->find('manifest'))) {
                    throw new Exceptions\SectionBuilderException('Path provided by --manifest is invalid.');
                }

                if(true == is_readable($manifest . DIRECTORY_SEPARATOR . 'config.php')) {
                    include $manifest . DIRECTORY_SEPARATOR . 'config.php';
                } elseif(true == file_exists($manifest . DIRECTORY_SEPARATOR . 'config.json')) {
                    $config = $manifest . DIRECTORY_SEPARATOR . 'config.json';
                    try{
                        $settings = Json\json_decode_file($config, true);
                    } catch(\JsonException $ex) {
                        throw new Exceptions\SectionBuilderException("Config file {$config} is not valid json. Returned: " . $ex->getMessage());
                    }
                } else {
                    throw new Exceptions\SectionBuilderException('The path specified by --manifest does not contain a config.php or config.json file.');
                }

                if (!is_array($settings) || !isset($settings['database'])) {
                    throw new Exceptions\SectionBuilderException('Config file found in location specified by --manifest does not appear to be a valid Symphony CMS config file.');
                }

                return array_merge(
                    $databaseCredentials,
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
            ->validator($databaseOptionValidator)
    )
    ->add(
        Cli\Input\InputTypeFactory::build('LongOption')
            ->name('database-user')
            ->flags(Cli\Input\AbstractInputType::FLAG_OPTIONAL | Cli\Input\AbstractInputType::FLAG_VALUE_REQUIRED)
            ->description('name of database user')
            ->default(null)
            ->validator($databaseOptionValidator)
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
                if(false == is_bool($context->find('database-pass'))) {
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
            ->validator($databaseOptionValidator)
    )
    ->add(
        Cli\Input\InputTypeFactory::build('LongOption')
            ->name('database-host')
            ->flags(Cli\Input\AbstractInputType::FLAG_OPTIONAL | Cli\Input\AbstractInputType::FLAG_VALUE_REQUIRED)
            ->description('host IP of database to use for deployment. Default is 127.0.0.1')
            ->default('127.0.0.1')
            ->validator($databaseOptionValidator)
    )
    ->add(
        Cli\Input\InputTypeFactory::build('LongOption')
            ->name('database-port')
            ->flags(Cli\Input\AbstractInputType::FLAG_OPTIONAL | Cli\Input\AbstractInputType::FLAG_VALUE_REQUIRED)
            ->description('port number of database server. Default is 3306')
            ->default('3306')
            ->validator($databaseOptionValidator)
    )
;

$argv = Cli\Input\InputHandlerFactory::build(
    'Argv',
    (new Cli\Input\InputCollection())
        ->add($collection->find('h'))
        ->add($collection->find('L')),
    Cli\Input\AbstractInputHandler::FLAG_BIND_SKIP_VALIDATION
);

if (true === $argv->find('h')) {
    [$name, $version, $description, $examples] = $manpage;

    echo Functions\Cli\manpage(
        $name,
        $version,
        $description,
        $collection,
        Colour::FG_GREEN,
        Colour::FG_WHITE,
        [
            'Examples' => $examples,
            'Support' => "If you believe you have found a bug, please report it using the GitHub issue tracker at https://github.com/pointybeard/symphony-section-builder/issues, or better yet, fork the library and submit a pull request.\r\n\r\nCopyright 2018-2019 Alannah Kearney. Use `-L` to see software licence information.\r\n",
        ]
    );
    exit;
}

if ($argv->find('L')) {
    echo file_get_contents(__DIR__.'/../LICENCE').PHP_EOL;
    exit;
}

try {
    // Now we do validation with the entire collection
    $argv->bind($collection);

    if (null === $argv->find('manifest') && (null === $argv->find('database-name') || null === $argv->find('database-user'))) {
        Functions\Cli\display_error_and_exit('Insufficent database credentials supplied. You must specify either --manifest or both --database-name & --database-user at a minimum', 'Invalid Options');
    }
} catch (Cli\Input\Exceptions\RequiredInputMissingException | Cli\Input\Exceptions\UnrecognisedInputException |  Cli\Input\Exceptions\RequiredInputMissingValueException $ex) {
    echo Colour::colourise($manpage[0].": {$ex->getMessage()}", Colour::FG_RED).PHP_EOL.Functions\Cli\usage($manpage[0], $collection).PHP_EOL.PHP_EOL.'Try `-h` for more options.'.PHP_EOL;
    exit(1);
} catch (Cli\Input\Exceptions\InputValidationFailedException $ex) {
    Functions\Cli\display_error_and_exit($ex->getMessage(), 'Invalid Options');
} catch (\Exception $ex) {
    Functions\Cli\display_error_and_exit('An error occurred while attempting to bind input values. Returned: '.$ex->getMessage(), 'FATAL ERROR');
}

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

try {
    \SymphonyPDO\Loader::instance((object) $databaseCredentials);
} catch (\Exception $ex) {
    Functions\Cli\display_error_and_exit('Unable to connect with the database credentials provided. Returned: '.$ex->getMessage(), 'FATAL ERROR');
}
