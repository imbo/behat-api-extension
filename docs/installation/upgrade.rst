Upgrading
=========

This section will cover breaking changes between major versions and other related information to ease upgrading to the latest version.

Migration from v5.x to v6.x
---------------------------

.. contents:: Changes
    :local:
    :depth: 1

PHP version requirement
^^^^^^^^^^^^^^^^^^^^^^^

``v6.x`` requires ``PHP >= 8.3``.

Migration from v4.x to v5.x
---------------------------

.. contents:: Changes
    :local:
    :depth: 1

Internal HTTP client configuration
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

Previous versions of the extension suggested using the ``GuzzleHttp\Client::getConfig()`` method to customize the internal HTTP client. This method has been deprecated, and the initialization of the internal HTTP client in the extension had to be changed as a consequence. Refer to the :ref:`configure-the-api-client` section for more information.

Migration from v3.x to v4.x
---------------------------

.. contents:: Changes
    :local:
    :depth: 1

PHP version requirement
^^^^^^^^^^^^^^^^^^^^^^^

``v4.x`` requires ``PHP >= 8.1``.

Type hints
^^^^^^^^^^

Type hints have been added to a plethora of the code base, so child classes will most likely break as a consequence. You will have to add missing type hints if you have extended any classes that have type hints added to them.

Migration from v2.x to v3.x
---------------------------

.. contents:: Changes
    :local:
    :depth: 1

The usage of Behat API Extension itself has not changed between these versions, but ``>=3.0`` requires ``PHP >= 7.4``.

Migrating from v1.x to v2.x
---------------------------

.. contents:: Changes
    :local:
    :depth: 1

Configuration change
^^^^^^^^^^^^^^^^^^^^

In ``v1`` the extension only had a single configuration option, which was ``base_uri``. This is still an option in ``v2``, but it has been added to an ``apiClient`` key.

**v1 behat.yml**

.. code-block:: yaml

    default:
      suites:
        default:
          # ...

      extensions:
        Imbo\BehatApiExtension:
          base_uri: http://localhost:8080

**v2 behat.yml**

.. code-block:: yaml

    default:
      suites:
        default:
          # ...

      extensions:
        Imbo\BehatApiExtension:
          apiClient:
            base_uri: http://localhost:8080

Renamed public methods
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
``thenTheResponseBodyIs``                             ``assertResponseBodyIs``
``thenTheResponseBodyMatches``                        ``assertResponseBodyMatches``
``thenTheResponseBodyContains``                       ``assertResponseBodyContainsJson``
====================================================  =========================================

Some methods have also been removed (as the result of removed steps):

* ``whenIRequestPathWithBody``
* ``whenIRequestPathWithJsonBody``
* ``whenISendFile``

Updated steps
^^^^^^^^^^^^^

``v1`` contained several ``When`` steps that could configure the request as well as sending it, in the same step. These steps has been removed in ``v2.0.0``, and the extension now requires you to configure all aspects of the request using the ``Given`` steps prior to issuing one of the few ``When`` steps.

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

Then the response body contains: ``<PyStringNode>``
"""""""""""""""""""""""""""""""""""""""""""""""""""

Slight change that adds "JSON" in the step text for clarification:

**v1**

.. code-block:: gherkin

    Then the response body contains:
        """
        {"some": "value"}
        """

**v2**

.. code-block:: gherkin

    Then the response body contains JSON:
        """
        {"some": "value"}
        """

Functions names for the JSON matcher
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

When recursively checking a JSON response body, some custom functions exist that is represented as the value in a key / value pair. Below is a table of all available functions in ``v1`` along with the updated names used in ``v2``:

======================  ========================
``v1`` function         ``v2`` function
======================  ========================
``@length(num)``        ``@arrayLength(num)``
``@atLeast(num)``       ``@arrayMinLength(num)``
``@atMost(num)``        ``@arrayMaxLength(num)``
``<re>/pattern/</re>``  ``@regExp(/pattern/)``
======================  ========================

``v2`` have also added more such functions, refer to the :ref:`custom-matcher-functions-and-targeting` section for a complete list.

Exceptions
^^^^^^^^^^

The extension will from ``v2`` on throw native PHP exceptions or namespaced exceptions (like for instance ``Imbo\BehatApiExtension\Exception\AssertionException``). In ``v1`` exceptions could come directly from ``beberlei/assert``, which is the assertion library used in the extension. The fact that the extension uses this library is an implementation detail, and it should be possible to switch out this library without making any changes to the public API of the extension.

If versions after ``v2`` throws other exceptions it should be classified as a bug and fixed accordingly.
