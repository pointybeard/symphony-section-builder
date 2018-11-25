# Symphony CMS: Section Builder

- Version: v0.1.2
- Date: November 25th 2018
- [Release notes](https://github.com/pointybeard/symphony-section-builder/blob/master/CHANGELOG.md)
- [GitHub repository](https://github.com/pointybeard/symphony-section-builder)

A set of classes that assist with the creating and updating of sections and their fields.

## Installation

This library is installed via [Composer](http://getcomposer.org/). To install, use `composer require pointybeard/symphony-section-builder` or add `"pointybeard/pointybeard/symphony-section-builder": "~0.1"` to your `composer.json` file.

And run composer to update your dependencies:

    $ curl -s http://getcomposer.org/installer | php
    $ php composer.phar update

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

## Support

If you believe you have found a bug, please report it using the [GitHub issue tracker](https://github.com/pointybeard/symphony-section-builder/issues),
or better yet, fork the library and submit a pull request.

## Contributing

We encourage you to contribute to this project. Please check out the [Contributing documentation](https://github.com/pointybeard/symphony-section-builder/blob/master/CONTRIBUTING.md) for guidelines about how to get involved.

## License

"Symphony CMS: Section Builder" is released under the [MIT License](http://www.opensource.org/licenses/MIT).
