Changelog for Behat API Extension
=================================

v2.1.0
------
__2018-01-20__

* #67: Pass in entire array in the `apiClient` part of the configuration to the Guzzle Client instead of specifying specific configuration options (@vitalyiegorov)
* #64: Move connectability validation of the `base_uri` option so that `behat --help` (amongst others) can be executed without validating the configuration (@oxkhar)
* #54: Added support for JWT matching using the `@jwt()` custom matcher function, which uses the [firebase/php-jwt](https://packagist.org/packages/firebase/php-jwt) package (@Zwartpet)

Bug fixes:

* #57: Use HTTP GET when no method is specified

Other changes:

- #56: Grammar fix (@FabianPiconeDev)

v2.0.1
------
__2017-04-09__

* #48: Allow HTTP PATCH (and other HTTP methods) with form parameters

v2.0.0
------
__2017-04-01__

* Removed and updated some steps and public methods (refer to [the docs](https://behat-api-extension.readthedocs.io) regarding upgrading)
* Added more steps (refer to [the guide](https://behat-api-extension.readthedocs.io) to see all available steps)

Other changes:

* #43: Matcher functions for greater than and less than
* #36: Improved documentation: https://behat-api-extension.readthedocs.io
* #29: New step: Assert response status line
* #19: New steps: Set request body to a string or a file before sending the request
* #18: New step: Assert response reason phrase

v1.0.4
------
__2016-10-26__

* #15: Add support for checking numerical arrays on root

v1.0.3
------
__2016-10-13__

Bug fixes:

* #13: Checking multi-dimensional arrays

v1.0.2
------
__2016-09-15__

* #8: Step(s) for working with form data

Bug fixes:

* #7: Don't allow request body when sending multipart/form-data requests
* #5: Attaching files does not work

v1.0.1
------
__2016-09-10__

* #3: Don't restrict comparisons to scalar values

Bug fixes:

* #1: Can't compare null values

v1.0.0
------
__2016-09-10__

* Initial release
