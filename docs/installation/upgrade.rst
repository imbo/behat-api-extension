Upgrading
=========

This section will cover breaking changes between major versions to ease upgrading to the latest version.

v2.0.0
------

Changed public methods
^^^^^^^^^^^^^^^^^^^^^^

The following public methods in the ``Imbo\BehatApiExtension\Context\ApiContext`` class have been renamed:

====================================================  =========================================
``v1`` method name                                    ``v2`` method name
====================================================  =========================================
``givenIAttachAFileToTheRequest``                     ``addMultipartFileToRequest``
``givenIAuthenticateAs``                              ``setBasicAuth``
``givenTheRequestHeaderIs``                           ``addRequestHeader``
``giventhefollowingformparametersareset``             ``setRequestFormParams``
``givenTheRequestBodyIs``                             ``setRequestBody``
``givenTheRequestBodyContains``                       ``setRequestBodyToFileResource``
``whenIRequestPath``                                  ``requestPath``
``thenTheResponseCodeIs``                             ``assertResponseCodeIs``
``thenTheResponseCodeIsNot``                          ``assertResponseCodeIsNot``
``thenTheResponseReasonPhraseIs``                     ``assertResponseReasonPhraseIs``
``thenTheResponseStatusLineIs``                       ``assertResponseStatusLineIs``
``thenTheResponseIs``                                 ``assertResponseIs``
``thenTheResponseIsNot``                              ``assertResponseIsNot``
``thenTheResponseHeaderExists``                       ``assertResponseHeaderExists``
``thenTheResponseHeaderDoesNotExist``                 ``assertResponseHeaderDoesNotExists``
``thenTheResponseHeaderIs``                           ``assertResponseHeaderIs``
``thenTheResponseHeaderMatches``                      ``assertResponseHeaderMatches``
``thenTheResponseBodyIsAnEmptyObject``                ``assertResponseBodyIsAnEmptyJsonObject``
``thenTheResponseBodyIsAnEmptyArray``                 ``assertResponseBodyIsAnEmptyJsonArray``
``thenTheResponseBodyIsAnArrayOfLength``              ``assertResponseBodyJsonArrayLength``
``thenTheResponseBodyIsAnArrayWithALengthOfAtLeast``  ``assertResponseBodyJsonArrayMinLength``
``thenTheResponseBodyIsAnArrayWithALengthOfAtMost``   ``assertResponseBodyJsonArrayMaxLength``
====================================================  =========================================

Some methods have also been removed (as the result of steps that can no longer be used):

* ``whenIRequestPathWithBody``
* ``whenIRequestPathWithJsonBody``
* ``whenISendFile``

Update steps
^^^^^^^^^^^^

``v1.*`` contained several ``When`` steps that could configure the request as well as sending it, in the same step. These steps has been removed in ``v2.0.0``, and the extension now requires you to configure all aspects of the request using the ``Given`` steps prior to issuing one of the few ``When`` steps.

.. contents:: Removed / updated steps
    :local:

Given the request body is ``:string``
"""""""""""""""""""""""""""""""""""""

This step now uses a ``<PyStringNode>`` instead of a regular string:

**v1**

.. code-block:: gherkin

    Given the request body is "some data"

**v2**

.. code-block:: gherkin

    Given the request body is:
        """
        some data
        """

When I request ``:path`` using HTTP ``:method`` with body: ``<PyStringNode>``
"""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""

The body needs to be set using a ``Given`` step and not in the ``When`` step:

**v1**

.. code-block:: gherkin

    When I request "/some/path" using HTTP POST with body:
        """
        {"some":"data"}
        """

**v2**

.. code-block:: gherkin

    Given the request body is:
        """
        {"some":"data"}
        """
    When I request "/some/path" using HTTP POST

When I request ``:path`` using HTTP ``:method`` with JSON body: ``<PyStringNode>``
""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""

The ``Content-Type`` header and body needs to be set using ``Given`` steps:

**v1**

.. code-block:: gherkin

    When I request "/some/path" using HTTP POST with JSON body:
        """
        {"some":"data"}
        """

**v2**

.. code-block:: gherkin

    Given the request body is:
        """
        {"some":"data"}
        """
    And the "Content-Type" request header is "application/json"
    When I request "/some/path" using HTTP POST

When I send ``:filePath`` (as ``:mimeType``) to ``:path`` using HTTP ``:method``
""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""

These steps must be replaced with the following:

**v1**

.. code-block:: gherkin

    When I send "/some/file.jpg" to "/some/endpoint" using HTTP POST

.. code-block:: gherkin

    When I send "/some/file" as "application/json" to "/some/endpoint" using HTTP POST

**v2**

.. code-block:: gherkin

    Given the request body contains "/some/file.jpg"
    When I request "/some/endpoint" using HTTP POST

.. code-block:: gherkin

    Given the request body contains "/some/file"
    And the "Content-Type" request header is "application/json"
    When I request "/some/endpoint" using HTTP POST

The first form in the old and new versions will guess the mime type of the file and set the ``Content-Type`` request header accordingly.

Then the response body is an empty object
"""""""""""""""""""""""""""""""""""""""""

Slight change that adds "JSON" in the step text for clarification:

**v1**

.. code-block:: gherkin

    Then the response body is an empty object

**v2**

.. code-block:: gherkin

    Then the response body is an empty JSON object

Then the response body is an empty array
""""""""""""""""""""""""""""""""""""""""

Slight change that adds "JSON" in the step text for clarification:

**v1**

.. code-block:: gherkin

    Then the response body is an empty array

**v2**

.. code-block:: gherkin

    Then the response body is an empty JSON array

Then the response body is an array of length ``:length``
""""""""""""""""""""""""""""""""""""""""""""""""""""""""

Slight change that adds "JSON" in the step text for clarification:

**v1**

.. code-block:: gherkin

    Then the response body is an array of length 5

**v2**

.. code-block:: gherkin

    Then the response body is a JSON array of length 5

Then the response body is an array with a length of at least ``:length``
""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""

Slight change that adds "JSON" in the step text for clarification:

**v1**

.. code-block:: gherkin

    Then the response body is an array with a length of at least 5

**v2**

.. code-block:: gherkin

    Then the response body is a JSON array with a length of at least 5

Then the response body is an array with a length of at most ``:length``
"""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""

Slight change that adds "JSON" in the step text for clarification:

**v1**

.. code-block:: gherkin

    Then the response body is an array with a length of at most 5

**v2**

.. code-block:: gherkin

    Then the response body is a JSON array with a length of at most 5
