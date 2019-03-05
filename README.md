# Symphony CMS: Section Builder

- Version: v0.1.7
- Date: March 6th 2019
- [Release notes](https://github.com/pointybeard/symphony-section-builder/blob/master/CHANGELOG.md)
- [GitHub repository](https://github.com/pointybeard/symphony-section-builder)

A set of classes that assist with the creating and updating of sections and their fields.

## Installation

This libary can be used standalone or as part of SymphonyCMS (including extensions) install.

### Standalone

Clone desired version from the GitHub repository with `git clone https://github.com/pointybeard/symphony-section-builder.git` then run `composer update` within that folder. Note, this will install dev library `symphonycms/symphony-2`.

### Using Composer

To install via [Composer](http://getcomposer.org/), use `composer require pointybeard/symphony-section-builder` or add `"pointybeard/pointybeard/symphony-section-builder": "~0.1"` to your `composer.json` file.

And run composer to update your dependencies, for example:

    $ curl -s http://getcomposer.org/installer | php
    $ php composer.phar update

Note that this method will NOT install any dev libraries, specifically `symphonycms/symphony-2`. Generally this is the desired behaviour, however, should the core SymphonyCMS library not get included anywhere via composer (e.g. Section Builder is being used as part of a library that doesn't already include SymphonyCMS), be use to use the `--dev` flag (e.g. `composer update --dev`) to ensure `symphonycms/symphony-2` is also installed.

## Usage

Quick example of how to use this library:

```php
    use pointybeard\Symphony\SectionBuilder\Lib;

    try {
        $categories = Lib\Models\Section::loadFromHandle('categories');
        if(!($categories instanceof Lib\Models\Section)) {
            $categories = (new Lib\Models\Section)
                ->name("Categories")
                ->handle("categories")
                ->navigationGroup("Content")
                ->allowFiltering(true)
                ->hideFromBackendNavigation(false)
                ->addField(
                    (new Lib\Models\Fields\Input)
                        ->label("Name")
                        ->elementName("name")
                        ->location(Lib\AbstractField::PLACEMENT_MAIN_CONTENT)
                        ->required(true)
                        ->showColumn(true)
                        ->validator("")
                )
                ->commit();
            ;
        }

        $articles = Lib\Models\Section::loadFromHandle('articles');
        if(!($articles instanceof Lib\Models\Section)) {
            $articles = (new Lib\Models\Section)
                ->name("Articles")
                ->handle("articles")
                ->navigationGroup("Content")
                ->allowFiltering(true)
                ->hideFromBackendNavigation(false)
                ->addField(
                    (new Lib\Models\Fields\Input)
                        ->label("Title")
                        ->elementName("title")
                        ->location(Lib\AbstractField::PLACEMENT_MAIN_CONTENT)
                        ->required(true)
                        ->showColumn(true)
                        ->validator("")
                )
                ->addField(
                    (new Lib\Models\Fields\Textarea)
                        ->label("Body")
                        ->elementName("body")
                        ->location(Lib\AbstractField::PLACEMENT_MAIN_CONTENT)
                        ->required(true)
                        ->showColumn(true)
                        ->size(10)
                        ->formatter(null)
                )
                ->addField(
                    (new Lib\Models\Fields\Date)
                        ->label("Created At")
                        ->elementName("date_created_at")
                        ->location(Lib\AbstractField::PLACEMENT_SIDEBAR)
                        ->required(true)
                        ->showColumn(true)
                        ->prePopulate("now")
                        ->calendar(false)
                        ->time(true)
                )
                ->addField(
                    (new Lib\Models\Fields\Select)
                        ->label("Categories")
                        ->elementName("categories")
                        ->location(Lib\AbstractField::PLACEMENT_SIDEBAR)
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
    use pointybeard\Symphony\SectionBuilder\Lib;

    try {
        Lib\Import::fromJsonFile("/path/to/some/file.json");
        print "Success!!" . PHP_EOL;

    } catch (\Exception $ex) {
        print "FAILED: " . $ex->getMessage() . PHP_EOL;
        var_dump($ex); die;
    }
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
                "authorId": 1,
                "modificationAuthorId": 1,
                "dateCreatedAt": "2018-11-01 14:21:07",
                "dateCreatedAtGMT": "2018-11-01 04:21:07",
                "dateModifiedAt": "2018-11-01 14:21:07",
                "dateModifiedAtGMT": "2018-11-01 04:21:07",
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
    use pointybeard\Symphony\SectionBuilder\Lib;
    $section = Lib\Models\Section::loadFromHandle('categories');

    print (string)$section;
    print $section->__toJson();
    print_r($section->__toArray());

```

If a full export is necessary, use the `all()` method and build the array before encoding it to JSON. e.g.

```php
    $output = ["sections" => []];
    foreach(Lib\Models\Section::all() as $section) {
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
use pointybeard\Symphony\SectionBuilder\Lib;

try {
    foreach(Lib\Diff::fromJsonFile("/path/to/some/file.json")){
        // Print changes found here ...
    }

} catch (\Exception $ex) {
    print "FAILED: " . $ex->getMessage() . PHP_EOL;
}
```

## Support

If you believe you have found a bug, please report it using the [GitHub issue tracker](https://github.com/pointybeard/symphony-section-builder/issues),
or better yet, fork the library and submit a pull request.

## Contributing

We encourage you to contribute to this project. Please check out the [Contributing documentation](https://github.com/pointybeard/symphony-section-builder/blob/master/CONTRIBUTING.md) for guidelines about how to get involved.

## License

"Symphony CMS: Section Builder" is released under the [MIT License](http://www.opensource.org/licenses/MIT).
