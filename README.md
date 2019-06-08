# Symphony CMS: Section Builder

-   Version: 0.2.0
-   Date: June 8 2019
-   [Release notes](https://github.com/pointybeard/symphony-section-builder/blob/master/CHANGELOG.md)
-   [GitHub repository](https://github.com/pointybeard/symphony-section-builder)

A set of classes that assist with the creating and updating of sections and their fields.

## Installation

This libary can be used standalone or as part of a [Symphony CMS](https://getsymphony.com) installation (including Extension).

### Standalone

Clone desired version from the GitHub repository with `git clone https://github.com/pointybeard/symphony-section-builder.git` then run `composer update` within that folder. Note, this will install dev library `symphonycms/symphony-2` by default. Use `--no-dev` when running `composer update` to skip this.

### Using Composer

To install via [Composer](http://getcomposer.org/), use `composer require pointybeard/symphony-section-builder` or add `"pointybeard/pointybeard/symphony-section-builder": "^0.2.0"` to your `composer.json` file.

And run composer to update your dependencies, for example:

    $ curl -s http://getcomposer.org/installer | php
    $ php composer.phar update

Note that this method will NOT install any dev libraries, specifically `symphonycms/symphony-2`. Generally this is the desired behaviour, however, should the core Symphony CMS library not get included anywhere via composer (e.g. Section Builder is being used as part of a library that doesn't already include Symphony CMS), be use to use the `--dev` flag (e.g. `composer update --dev`) to ensure `symphonycms/symphony-2` is also installed, or, use the `--symphony=PATH` option to tell Section Builder where to load the Symphony CMS core from.

## Usage

Quick example of how to use this library:

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

### Importing JSON

Run `bin/import` from the command line or use code like this:

```php
<?php
use pointybeard\Symphony\SectionBuilder;
SectionBuilder\Import::fromJsonFile('/path/to/some/file.json');
```

Use flag `FLAG_SKIP_ORDERING` if importing partial section JSON. This helps
to avoid a circular dependency exception being thrown. Flags are supported by `fromJsonFile()`, `fromJsonString()`, and `fromObject()`. For example:

```php
<?php
SectionBuilder\Import::fromJsonFile('/path/to/some/file.json', SectionBuilder\Import::FLAG_SKIP_ORDERING);
```

JSON must be an array of sections and look like this:

```json
{
    "sections": [
        {
            "name": "Providers",
            "handle": "providers",
            "sortOrder": 39,
            "hideFromBackendNavigation": false,
            "allowFiltering": false,
            "navigationGroup": "Shipping",
            "associations": [],
            "fields": [
                {
                    "label": "Name",
                    "elementName": "name",
                    "type": "input",
                    "required": true,
                    "sortOrder": 0,
                    "location": "sidebar",
                    "showColumn": true,
                    "custom": {
                        "validator": null
                    }
                },
                {
                    "label": "UUID",
                    "elementName": "uuid",
                    "type": "uuid",
                    "required": true,
                    "sortOrder": 1,
                    "location": "sidebar",
                    "showColumn": true,
                    "custom": []
                }
            ]
        }
    ]
}

```

### Exporting

Run `bin/export` from the command line or use the `__toString()`, `__toJson()`, and/or `__toArray()` methods provided by `AbstractField` and `Section`. For example:

```php
<?php
use pointybeard\Symphony\SectionBuilder\Models;
$section = Models\Section::loadFromHandle('categories');

print (string)$section;
print $section->__toJson();
print_r($section->__toArray());

```

If a full export is necessary, use the `all()` method and build the array before encoding it to JSON. e.g.

```php
<?php
$output = ["sections" => []];
foreach(Models\Section::all() as $section) {
    // We use json_decode() to ensure ids (id and sectionId) are removed
    // from the output. To keep ids, use __toArray()
    $output["sections"][] = json_decode((string)$section, true);
}

print json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

```

Note that IDs (specifically Section and Field `id` and Field `sectionId` properties) are automatically stripped out by `__toString()` and `__toJson()`. To keep them, either use `__toArray()` and encode to JSON yourself, or using `__toJson()` but set `$excludeIds` to false. e.g. `$section->__toJson(false)`. See this implementation in the Trait `hasToStringToJsonTrait`.

### Diff

You can compare a database with a JSON export via `bin/diff` from the command line or use code like this:

```php
<?php
use pointybeard\Symphony\SectionBuilder;

foreach(SectionBuilder\Diff::fromJsonFile('/path/to/some/file.json')){
    // Print changes found here ...
}

```

## Support

If you believe you have found a bug, please report it using the [GitHub issue tracker](https://github.com/pointybeard/symphony-section-builder/issues),
or better yet, fork the library and submit a pull request.

## Contributing

We encourage you to contribute to this project. Please check out the [Contributing documentation](https://github.com/pointybeard/symphony-section-builder/blob/master/CONTRIBUTING.md) for guidelines about how to get involved.

## License

"Symphony CMS: Section Builder" is released under the [MIT License](http://www.opensource.org/licenses/MIT).
