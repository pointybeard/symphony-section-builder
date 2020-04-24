# Symphony CMS: Section Builder

[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/pointybeard/symphony-section-builder/badges/quality-score.png?b=master)][ext-scrutinizer]
[![Code Coverage](https://scrutinizer-ci.com/g/pointybeard/symphony-section-builder/badges/coverage.png?b=master)][ext-scrutinizer]
[![Build Status](https://scrutinizer-ci.com/g/pointybeard/symphony-section-builder/badges/build.png?b=master)][ext-scrutinizer]

A set of classes and command line scripts that assist with the creating, exporting, and updating of sections and their fields.

-   [Installation](#installation)
-   [Basic Usage](#basic-usage)
-   [About](#about)
    -   [Requirements](#dependencies)
    -   [Dependencies](#dependencies)
-   [Documentation](#documentation)
-   [Support](#support)
-   [Contributing](#contributing)
-   [License](#license)

## Installation

This libary can be used standalone or as part of a [Symphony CMS][ext-Symphony] installation (including Extension) via composer.

### Standalone

Use the following commands to clone this repository and install required packages

```bash
$ git clone https://github.com/pointybeard/symphony-section-builder.git
$ composer update -vv --profile -d ./symphony-section-builder
```

### Via Composer

To install via [Composer](http://getcomposer.org/), use 

```bash
$ composer require pointybeard/symphony-section-builder
```

Alternatively, add `"pointybeard/pointybeard/symphony-section-builder": "~0.2.0"` to your `composer.json` file's `require` or `require-dev` block and update your project to install the new dependencies with

```bash
$ composer update -vv --profile
```

Note that this method will NOT install any dev libraries, specifically `symphonycms/symphonycms`. Generally this is the desired behaviour, however, should the core Symphony CMS library not get included anywhere via composer (e.g. Section Builder is being used as part of a library that doesn't already include Symphony CMS), be sure to use the `--dev` flag (e.g. `composer update --dev`) so `symphonycms/symphonycms` is installed. Alternatively, use the `--symphony=PATH` option on the command line to tell Section Builder where to load the Symphony CMS core from. E.g.

```bash
$ vendor/bin/section-builder export --symphony=/var/www/mywebsite --manifest=/path/to/manifest
```

## Basic Usage

Invoke Section Builder from the command line with the following

```bash
## Import
$ bin/section-builder import -j /path/to/sections.json --manifest=/path/to/manifest

## Export
$ bin/section-builder export -o /path/to/outputfile.json --manifest=/path/to/manifest

## Diff
$ bin/section-builder diff -j /path/to/sections.json --manifest=/path/to/manifest
```

Section Builder can also be invoked within code. Here is a quick example:

```php
<?php
use pointybeard\Symphony\SectionBuilder;
use pointybeard\Symphony\SectionBuilder\Models;

try {
    $categories = Models\Section::loadFromHandle('categories');
    if(!($categories instanceof Models\Section)) {
        $categories = (new Models\Section)
            ->name("Categories")
            ->handle("categories")
            ->navigationGroup("Content")
            ->allowFiltering(true)
            ->hideFromBackendNavigation(false)
            ->addField(
                (new Models\Fields\Input)
                    ->label("Name")
                    ->elementName("name")
                    ->location(SectionBuilder\AbstractField::PLACEMENT_MAIN_CONTENT)
                    ->required(true)
                    ->showColumn(true)
                    ->validator("")
            )
            ->commit();
        ;
    }

    $articles = Models\Section::loadFromHandle('articles');
    if(!($articles instanceof Models\Section)) {
        $articles = (new Models\Section)
            ->name("Articles")
            ->handle("articles")
            ->navigationGroup("Content")
            ->allowFiltering(true)
            ->hideFromBackendNavigation(false)
            ->addField(
                (new Models\Fields\Input)
                    ->label("Title")
                    ->elementName("title")
                    ->location(SectionBuilder\AbstractField::PLACEMENT_MAIN_CONTENT)
                    ->required(true)
                    ->showColumn(true)
                    ->validator("")
            )
            ->addField(
                (new Models\Fields\Textarea)
                    ->label("Body")
                    ->elementName("body")
                    ->location(SectionBuilder\AbstractField::PLACEMENT_MAIN_CONTENT)
                    ->required(true)
                    ->showColumn(true)
                    ->size(10)
                    ->formatter(null)
            )
            ->addField(
                (new Models\Fields\Date)
                    ->label("Created At")
                    ->elementName("date_created_at")
                    ->location(SectionBuilder\AbstractField::PLACEMENT_SIDEBAR)
                    ->required(true)
                    ->showColumn(true)
                    ->prePopulate("now")
                    ->calendar(false)
                    ->time(true)
            )
            ->addField(
                (new Models\Fields\Select)
                    ->label("Categories")
                    ->elementName("categories")
                    ->location(SectionBuilder\AbstractField::PLACEMENT_SIDEBAR)
                    ->required(true)
                    ->showColumn(true)
                    ->allowMultipleSelection(true)
                    ->sortOptions(true)
                    ->staticOptions(null)
                    ->dynamicOptions(
                        $categories->findFieldByElementName("name")
                    )
            )
            ->commit()
        ;
    }

    print "Success!!" . PHP_EOL;

} catch (\Exception $ex) {
    print "FAILED: " . $ex->getMessage() . PHP_EOL;
}
```

## About

### Requirements

- This extension works with PHP 7.3 or above.

### Dependencies

Section Builder depends on the following Composer libraries:

-   [pointybeard/symphony-pdo][dep-symphony-pdo]
-   [pointybeard/helpers][dep-helpers]
-   [pointybeard/property-bag][dep-property-bag]

As well as the following dev libraries

-   The Symphony CMS (Extended) fork of [symphonycms/symphonycms][dep-symphonycms]
-   [squizlabs/php_codesniffer][dep-php_codesniffer]
-   [friendsofphp/php-cs-fixer][dep-friendsofphp/php-cs-fixer]
-   [damianopetrungaro/php-commitizen][dep-php-commitizen]
-   [php-parallel-lint/php-parallel-lint][dep-php-parallel-lint]

## Documentation

Read the [full documentation here][ext-docs].

## Support

If you believe you have found a bug, please report it using the [GitHub issue tracker][ext-issues],
or better yet, fork the library and submit a pull request.

## Contributing

We encourage you to contribute to this project. Please check out the [Contributing to this project][doc-CONTRIBUTING] documentation for guidelines about how to get involved.

## Author
-   Alannah Kearney - hi@alannahkearney.com - http://twitter.com/pointybeard
-   See also the list of [contributors][ext-contributor] who participated in this project

## License
"Symphony CMS: Section Builder" is released under the MIT License. See [LICENCE][doc-LICENCE] for details.

[doc-CONTRIBUTING]: https://github.com/pointybeard/symphony-section-builder/blob/master/CONTRIBUTING.md
[doc-LICENCE]: http://www.opensource.org/licenses/MIT
[dep-helpers]: https://github.com/pointybeard/helpers
[dep-symphonycms]: https://github.com/pointybeard/symphonycms
[dep-symphony-pdo]: https://github.com/pointybeard/symphony-pdo
[dep-property-bag]: https://github.com/pointybeard/property-bag
[dep-php_codesniffer]: https://github.com/squizlabs/php_codesniffer
[dep-friendsofphp/php-cs-fixer]: https://github.com/friendsofphp/php-cs-fixer
[dep-php-commitizen]: https://github.com/damianopetrungaro/php-commitizen
[dep-php-parallel-lint]: https://github.com/php-parallel-lint/php-parallel-lint
[ext-issues]: https://github.com/pointybeard/symphony-section-builder/issues
[ext-Symphony]: http://getsymphony.com
[ext-contributor]: https://github.com/pointybeard/symphony-section-builder/contributors
[ext-docs]: https://github.com/pointybeard/symphony-section-builder/blob/master/.docs/toc.md
[ext-scrutinizer]: https://scrutinizer-ci.com/g/pointybeard/symphony-section-builder/?branch=master
