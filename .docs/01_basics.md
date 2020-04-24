# Basics

## JSON Section Structure

Section JSON is an array of sections with properties and fields describing it. Here is an example of what it should look like

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

## Importing

### Command Line

Use the following to import from the command line

```base
$ bin/import -j /path/to/sections.json --manifest=/path/to/manifest
```

Use the `--help` flag to see a full list of flags and options for importing.

### In Code

Importing can be invoked within the code of your project like so

```php
<?php
use pointybeard\Symphony\SectionBuilder;
SectionBuilder\Import::fromJsonFile('/path/to/some/file.json');
```

For partial section import (i.e. importing a single section into an existing Symphony install) use the `FLAG_SKIP_ORDERING` flag. E.g.

```php
SectionBuilder\Import::fromJsonFile('/path/to/some/file.json', SectionBuilder\Import::FLAG_SKIP_ORDERING);
```

Without using this flag, you will most likely encounter a circular dependency exception. Flags are supported by `fromJsonFile()`, `fromJsonString()`, and `fromObject()`.

## Exporting

Exporting sections from a Symphony CMS build will produce JSON output suitable for importing.

### Command Line

Use the following to export from the command line

```base
$ bin/export -o /path/to/output/sections.json --manifest=/path/to/manifest
```

Use the `--help` flag to see a full list of flags and options for exporting.

### In Code

Exporting can be invoked within the code of your project by using the `__toString()`, `__toJson()`, and/or `__toArray()` methods provided by `AbstractField` and `Section`. For example:

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

Note that ID values (specifically Section and Field `id` and Field `sectionId` properties) are automatically stripped out by `__toString()` and `__toJson()`. To keep them, either use `__toArray()` and encode to JSON yourself, or using `__toJson()` but set `$excludeIds` to false. e.g. `$section->__toJson(false)`. See this implementation in the Trait `hasToStringToJsonTrait`.

# Diff

Diff allows you to see the differences between a Symphony CMS installation and a previously exported JSON file.

### Command Line

Use the following to perform a diff on the command line

```base
$ bin/diff -j /path/to/sections.json --manifest=/path/to/manifest
```

Use the `--help` flag to see a full list of flags and options for the diff command.

### In Code

Diff can also be invoked directly within your code like so

```php
<?php
use pointybeard\Symphony\SectionBuilder;

foreach(SectionBuilder\Diff::fromJsonFile('/path/to/some/file.json')){
    // Print changes found here ...
}
```
