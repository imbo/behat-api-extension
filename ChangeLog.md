Changelog for Behat API Extension
=================================

v4.0.0
------
__N/A__

* [#125](https://github.com/imbo/behat-api-extension/pull/125): Require PHP >= 8.1

v3.0.1
------
__2022-06-27__

* [#109](https://github.com/imbo/behat-api-extension/pull/109): Step for asserting an empty response body
* [#106](https://github.com/imbo/behat-api-extension/issues/106): Adopt coding standard

v3.0.0
------
__2021-05-25__

* [#101](https://github.com/imbo/behat-api-extension/issues/101): Require PHP >= 7.4
* [#92](https://github.com/imbo/behat-api-extension/pull/92): Add password grant OAuth step ([@ABGEO](https://github.com/ABGEO))
* [#85](https://github.com/imbo/behat-api-extension/pull/85): Add support for manipulating query parameters using steps

v2.3.1
------
__2020-01-29__

* Minor docs fix and bumped copyright year

v2.3.0
------
__2020-01-29__

* [#84](https://github.com/imbo/behat-api-extension/pull/84): Added support for `any` and multiple variable types with the `@variableType` matcher

v2.2.1
------
__2019-09-15__

* [#74](https://github.com/imbo/behat-api-extension/pull/74): Docs fix ([@adambro](https://github.com/adambro))

v2.2.0
------
__2019-04-04__

* [#78](https://github.com/imbo/behat-api-extension/pull/78): Added new step for sending multipart form data ([@miteshmap](https://github.com/miteshmap))

v2.1.0
------
__2018-01-20__

* [#67](https://github.com/imbo/behat-api-extension/pull/67): Pass in entire array in the `apiClient` part of the configuration to the Guzzle Client instead of specifying specific configuration options ([@vitalyiegorov](https://github.com/vitalyiegorov))
* [#64](https://github.com/imbo/behat-api-extension/pull/64): Move connectability validation of the `base_uri` option so that `behat --help` (amongst others) can be executed without validating the configuration ([@oxkhar](https://github.com/oxkhar))
* [#54](https://github.com/imbo/behat-api-extension/pull/54): Added support for JWT matching using the `@jwt()` custom matcher function, which uses the [firebase/php-jwt](https://packagist.org/packages/firebase/php-jwt) package ([@Zwartpet](https://github.com/Zwartpet))

Bug fixes:

* [#57](https://github.com/imbo/behat-api-extension/pull/57): Use HTTP GET when no method is specified

Other changes:

* [#56](https://github.com/imbo/behat-api-extension/pull/56): Grammar fix ([@FabianPiconeDev](https://github.com/FabianPiconeDev))

v2.0.1
------
__2017-04-09__

* [#48](https://github.com/imbo/behat-api-extension/pull/48): Allow HTTP PATCH (and other HTTP methods) with form parameters

v2.0.0
------
__2017-04-01__

* Removed and updated some steps and public methods (refer to [the docs](https://behat-api-extension.readthedocs.io) regarding upgrading)
* Added more steps (refer to [the guide](https://behat-api-extension.readthedocs.io) to see all available steps)

Other changes:

* [#43](https://github.com/imbo/behat-api-extension/issues/43): Matcher functions for greater than and less than
* [#36](https://github.com/imbo/behat-api-extension/issues/36): Improved documentation: https://behat-api-extension.readthedocs.io
* [#29](https://github.com/imbo/behat-api-extension/issues/29): New step: Assert response status line
* [#19](https://github.com/imbo/behat-api-extension/issues/19): New steps: Set request body to a string or a file before sending the request
* [#18](https://github.com/imbo/behat-api-extension/issues/18): New step: Assert response reason phrase

v1.0.4
------
__2016-10-26__

* [#15](https://github.com/imbo/behat-api-extension/issues/15): Add support for checking numerical arrays on root

v1.0.3
------
__2016-10-13__

Bug fixes:

* [#13](https://github.com/imbo/behat-api-extension/issues/13): Checking multi-dimensional arrays

v1.0.2
------
__2016-09-15__

* [#8](https://github.com/imbo/behat-api-extension/issues/8): Step(s) for working with form data

Bug fixes:

* [#7](https://github.com/imbo/behat-api-extension/issues/7): Don't allow request body when sending multipart/form-data requests
* [#5](https://github.com/imbo/behat-api-extension/issues/5): Attaching files does not work

v1.0.1
------
__2016-09-10__

* [#3](https://github.com/imbo/behat-api-extension/issues/3): Don't restrict comparisons to scalar values

Bug fixes:

* [#1](https://github.com/imbo/behat-api-extension/issues/1): Can't compare null values

v1.0.0
------
__2016-09-10__

* Initial release
