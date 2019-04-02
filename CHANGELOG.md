# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

## 0.1.12 - 2019-04-02
#### Fixed
- No longer necessary to specify section creation dates when importing

## 0.1.11 - 2019-03-20
#### Fixed
- Fixed usage information in `bin/export`

#### Added
- Added flag `Import::FLAG_SKIP_ORDERING`.
- Added ability to pass flags into `Import::fromJsonString()`, `Import::fromJsonFile()`, and `Import::FromObject()`.

## 0.1.10 - 2019-03-15
#### Fixed
- Fixed field id accessor in `hasFetchAssociatedFieldTrait` trait

## 0.1.9 - 2019-03-10
#### Fixed
- Added Uuid field model (previously UUID) to avoid namespace case issues on case sensitive operating systems. Removed UUID field model in order to rename to Uuid

## 0.1.8 - 2019-03-10
#### Added
- Added `CorruptFieldException` exception which is used by `AbstractField::loadFromId()`

#### Changed
- Throwing `CorruptFieldException` if there is a problem in `AbstractField::loadFromId()`
-
#### Fixed
- Setting field ID correctly if `AbstractField::commit()` triggers an update instead of insert.

## 0.1.7 - 2019-03-06
#### Fixed
- Removed `LIMIT 1` from `SectionAssociation::fetchByChildSectionId()` which Prevented more than a single association from being returned.
- Fixed ordering issue when generating an export (Closes #1)

## 0.1.6 - 2019-02-23
#### Changed
- Showing result count when running `bin/diff`
- Passing section handle to call of `AbstractField::loadFromElementName()` in `Lib\Diff`
- Added `sectionHandle` argument to `AbstractField::loadFromElementName()` method.
- Updated `AbstractField::loadFromElementName()` to use `sectionHandle` in SQL, ensuring the correct field is retrieved (i.e. not just the first found with `elementName`)

#### Fixed
- Fixed example in `--usage` for `bin/export`

#### Added
- Added `phpunit/phpunit` and moved `symphonycms/symphony2` to require-dev composer definition.

## 0.1.4 - 2018-11-28
#### Added
- Added test data for import and diff
- Added `symphonycms/symphony-2` and `pointybeard/php-cli-lib` to composer
- Added `--help` information
- Using `CLILib` for parsing arguments and displaying messages in `bin/diff`, `bin/import`, and `bin/export`

#### Changed
- Changed AbstractTableModel::isFlagSet() from protected to public
- Added extra checks to Select model `hasAssociations()`, `associationParentSectionId()`, and `associationParentSectionFieldId()` methods to ensure 'dynamicOptions' is an instance of `Property` before trying to test the value
- Renamed `DiffRecord` to `Record` and placed inside `/Diff` folder. Updated Diff class to reflect this change
- Throwing exception if model for field type cannot be located when attempting to import
- Changed self to static when calling getFieldMappings() in the constructor. This ensures custom properties in the child class are set initialised correctly
- Throwing exception if unable to find field ID from provided element name in `AbstractField::loadFromElementName()`
- Throwing exception if unable to load field from provided ID in `AbstractField::loadFromId()`

## 0.1.3 - 2018-11-28
#### Added
* Implemented `__toArray()` abstract method in Section, AbstractField, and SectionAssociation models
* Added `hasToStringToJsonTrait` Trait. This provides `__toJson()` and `__toString()`
* Added `fromObject()` method, and moved code from the `fromJson()` method into `fromObject()` in the Import class
* Added abstract `__toArray()` method, `all()` method in `AbstractTableModel`
* Using `hasToStringToJsonTrait` Trait in `AbstractTableModel` which allow means `__toJson()` and `__toString()` trickle down to AbstractField, Section, and SectionAssociation

## 0.1.2 - 2018-11-25
#### Added
Added additional field models: Order ID, Primary Entry, and Reverse Regex

#### Changed
PHP CS Fixer run over code base
Changed field mapping of Select field so `dynamicOptions` can be NULL
Modified testing for NULL in `AbstractTableModel::enforceType()`. If it's not NULL, it will fall through to the other types

#### Fixed
Handling circular dependencies and fields that link to other fields in the same section.
Fixed default state of Checkbox field so 'on' converts to 'yes' and 'off' to 'no'

## 0.1.1 - 2018-11-25
#### Added
- Added UUID and Unique Input field models
- Added Import class that will generate sections from a JSON file or string

#### Changed
- Renamed `Selectbox_Link` to `SelectboxLink`
- Made `fieldTypeToClassName` and `fieldTypeToAttributeTableName` public methods
- Overloaded `hasAssociations()` in Select and Select Box Link models. Also added checks to `associationParentSectionId()` and `associationParentSectionFieldId()`

#### Fixed
- Fixed instructions on adding library via composer to README
- Fixed typo in field mappings for Textarea field model

## 0.1.0 - 2018-11-24
#### Added
- Initial release
