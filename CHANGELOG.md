# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

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
