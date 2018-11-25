# Change Log
All notable changes to this project will be documented in this file.
This project adheres to [Semantic Versioning](http://semver.org/).

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
