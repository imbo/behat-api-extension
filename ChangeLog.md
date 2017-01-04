Changelog for Behat API Extension
=================================

v2.0.0
------
__N/A__

Renamed some public methods in the `Imbo\BehatApiExtension\Context\ApiContext` class:

    * `givenIAttachAFileToTheRequest` => `addMultipartFileToRequest`
    * `givenIAuthenticateAs` => `setBasicAuth`
    * `givenTheRequestHeaderIs` => `addRequestHeader`
    * `giventhefollowingformparametersareset` => `setRequestFormParams`
    * `givenTheRequestBodyIs` => `setRequestBody`
    * `givenTheRequestBodyContains` => `setRequestBodyToFileResource`
    * `whenIRequestPath` => `requestPath`

Other changes:

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
