Verify server response
======================

After a request has been sent, some steps exist that can be used to verify the response from the server.

.. contents:: Available steps
    :depth: 2
    :local:

Then the response code is ``:code``
-----------------------------------

Asserts that the response code equals ``:code``.

**Examples:**

* Then the response code is ``200``
* Then the response code is ``404``

Then the response code is not ``:code``
---------------------------------------

Asserts that the response code **does not** equal ``:code``.

**Examples:**

* Then the response code is not ``200``
* Then the response code is not ``404``

Then the response reason phrase is ``:phrase``
----------------------------------------------

Assert that the response reason phrase equals ``:phrase``. The comparison is case sensitive.

**Examples:**

* Then the response reason phrase is "``OK``"
* Then the response reason phrase is "``Bad Request``"

Then the response reason phrase is not ``:phrase``
--------------------------------------------------

Assert that the response reason phrase does not equal ``:phrase``. The comparison is case sensitive.

**Examples:**

* Then the response reason phrase is not "``OK``"
* Then the response reason phrase is not "``Bad Request``"

Then the response reason phrase matches ``:pattern``
----------------------------------------------------

Assert that the response reason phrase matches the regular expression ``:pattern``. The pattern must be a valid regular expression, including delimiters, and can also include optional modifiers.

**Examples:**

* Then the response reason phrase matches "``/ok/i``"
* Then the response reason phrase matches "``/OK/``"

For more information regarding regular expressions and the usage of modifiers, `refer to the PHP manual <http://php.net/pcre>`_.

Then the response status line is ``:line``
------------------------------------------

Assert that the response status line equals ``:line``. The comparison is case sensitive.

**Examples:**

* Then the response status line is "``200 OK``"
* Then the response status line is "``304 Not Modified``"

Then the response status line is not ``:line``
----------------------------------------------

Assert that the response status line does not equal ``:line``. The comparison is case sensitive.

**Examples:**

* Then the response status line is not "``200 OK``"
* Then the response status line is not "``304 Not Modified``"

Then the response status line matches ``:pattern``
--------------------------------------------------

Assert that the response status line matches the regular expression ``:pattern``. The pattern must be a valid regular expression, including delimiters, and can also include optional modifiers.

**Examples:**

* Then the response status line matches "``/200 ok/i``"
* Then the response status line matches "``/200 OK/``"

For more information regarding regular expressions and the usage of modifiers, `refer to the PHP manual <http://php.net/pcre>`_.

Then the response is ``:group``
-------------------------------

Asserts that the response is in ``:group``.

Allowed groups and their response code ranges are:

=================  ===================
Group              Response code range
=================  ===================
``informational``  100 to 199
``success``        200 to 299
``redirection``    300 to 399
``client error``   400 to 499
``server error``   500 to 599
=================  ===================

**Examples:**

* Then the response is "``informational``"
* Then the response is "``client error``"

Then the response is not ``:group``
-----------------------------------

Assert that the response is not in ``:group``.

Allowed groups and their ranges are:

=================  ===================
Group              Response code range
=================  ===================
``informational``  100 to 199
``success``        200 to 299
``redirection``    300 to 399
``client error``   400 to 499
``server error``   500 to 599
=================  ===================

**Examples:**

* Then the response is not "``informational``"
* Then the response is not "``client error``"

Then the ``:header`` response header exists
-------------------------------------------

Assert that the ``:header`` response header exists. The value of ``:header`` is case-insensitive.

**Examples:**

* Then the "``Vary``" response header exists
* Then the "``content-length``" response header exists

Then the ``:header`` response header does not exist
---------------------------------------------------

Assert that the ``:header`` response header does not exist. The value of ``:header`` is case-insensitive.

**Examples:**

* Then the "``Vary``" response header does not exist
* Then the "``content-length``" response header does not exist

Then the ``:header`` response header is ``:value``
--------------------------------------------------

Assert that the value of the ``:header`` response header equals ``:value``. The value of ``:header`` is case-insensitive, but the value of ``:value`` is not.

**Examples:**

* Then the "``Content-Length``" response header is "``15000``"
* Then the "``X-foo``" response header is "``foo, bar``"

Then the ``:header`` response header is not ``:value``
------------------------------------------------------

Assert that the value of the ``:header`` response header **does not** equal ``:value``. The value of ``:header`` is case-insensitive, but the value of ``:value`` is not.

**Examples:**

* Then the "``Content-Length``" response header is not "``15000``"
* Then the "``X-foo``" response header is not "``foo, bar``"

Then the ``:header`` response header matches ``:pattern``
---------------------------------------------------------

Assert that the value of the ``:header`` response header matches the regular expression ``:pattern``. The pattern must be a valid regular expression, including delimiters, and can also include optional modifiers. The value of ``:header`` is case-insensitive.

**Examples:**

* Then the "``content-length``" response header matches "``/[0-9]+/``"
* Then the "``x-foo``" response header matches "``/(FOO|BAR)/i``"
* Then the "``X-FOO``" response header matches "``/^(foo|bar)$/``"

For more information regarding regular expressions and the usage of modifiers, `refer to the PHP manual <http://php.net/pcre>`_.

Then the response body is an empty JSON object
----------------------------------------------

Assert that the response body is an empty JSON object (``{}``).

Then the response body is an empty JSON array
---------------------------------------------

Assert that the response body is an empty JSON array (``[]``).

.. _then-the-response-body-is-an-array-of-length:

Then the response body is a JSON array of length ``:length``
------------------------------------------------------------

Assert that the length of the JSON array in the response body equals ``:length``.

**Examples:**

* Then the response body is a JSON array of length ``1``
* Then the response body is a JSON array of length ``3``

If the response body does not contain a JSON array, the test will fail.

Then the response body is a JSON array with a length of at least ``:length``
----------------------------------------------------------------------------

Assert that the length of the JSON array in the response body has a length of at least ``:length``.

**Examples:**

* Then the response body is a JSON array with a length of at least ``4``
* Then the response body is a JSON array with a length of at least ``5``

If the response body does not contain a JSON array, the test will fail.

Then the response body is a JSON array with a length of at most ``:length``
---------------------------------------------------------------------------

Assert that the length of the JSON array in the response body has a length of at most ``:length``.

**Examples:**

* Then the response body is a JSON array with a length of at most ``4``
* Then the response body is a JSON array with a length of at most ``5``

If the response body does not contain a JSON array, the test will fail.

Then the response body is: ``<PyStringNode>``
---------------------------------------------

Assert that the response body equals the text found in the ``<PyStringNode>``. The comparison is case-sensitive.

**Examples:**

.. code-block:: gherkin

    Then the response body is:
        """
        {"foo":"bar"}
        """

.. code-block:: gherkin

    Then the response body is:
        """
        foo
        """

Then the response body is not: ``<PyStringNode>``
-------------------------------------------------

Assert that the response body **does not** equal the value found in ``<PyStringNode>``. The comparison is case sensitive.

**Examples:**

.. code-block:: gherkin

    Then the response body is not:
        """
        some value
        """

Then the response body matches: ``<PyStringNode>``
--------------------------------------------------

Assert that the response body matches the regular expression pattern found in ``<PyStringNode>``. The expression must be a valid regular expression, including delimiters and optional modifiers.

**Examples:**

.. code-block:: gherkin

    Then the response body matches:
        """
        /^{"FOO": ?"BAR"}$/i
        """

.. code-block:: gherkin

    Then the response body matches:
        """
        /foo/
        """

.. _then-the-response-body-contains-json:

Then the response body contains JSON: ``<PyStringNode>``
--------------------------------------------------------

Used to recursively match the response body (or a subset of the response body) against a JSON blob.

In addition to regular value matching some custom matching-functions also exist, for asserting value types, array lengths and so forth. There is also a regular expression type matcher that can be used to match string values.

Regular value matching
^^^^^^^^^^^^^^^^^^^^^^

Assume the following JSON response for the examples in this section:

.. code-block:: json

    {
      "string": "string value",
      "integer": 123,
      "double": 1.23,
      "boolean": true,
      "null": null,
      "object":
      {
        "string": "string value",
        "integer": 123,
        "double": 1.23,
        "boolean": true,
        "null": null,
        "object":
        {
          "string": "string value",
          "integer": 123,
          "double": 1.23,
          "boolean": true,
          "null": null
        }
      },
      "array":
      [
        "string value",
        123,
        1.23,
        true,
        null,
        {
          "string": "string value",
          "integer": 123,
          "double": 1.23,
          "boolean": true,
          "null": null
        }
      ]
    }

**Example: Regular value matching of a subset of the response**

.. code-block:: gherkin

    Then the response body contains JSON:
        """
        {
          "string": "string value",
          "boolean": true
        }
        """

**Example: Check values in objects**

.. code-block:: gherkin

    Then the response body contains JSON:
        """
        {
          "object":
          {
            "string": "string value",
            "object":
            {
              "null": null,
              "integer": 123
            }
          }
        }
        """

**Example: Check numerically indexed array contents**

.. code-block:: gherkin

    Then the response body contains JSON:
        """
        {
          "array":
          [
            true,
            "string value",
            {
              "integer": 123
            }
          ]
        }
        """

Notice that the order of the values in the arrays does not matter. To be able to target specific indexes in an array a special syntax needs to be used. Please refer to :ref:`custom-matcher-functions-and-targeting` for more information and examples.

.. _custom-matcher-functions-and-targeting:

Custom matcher functions and targeting
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

In some cases the need for more advanced matching arises. All custom functions is used in place of the string value they are validating, and because of the way JSON works, they need to be specified as strings to keep the JSON valid.

.. contents::
    :local:

Array length - ``@arrayLength`` / ``@arrayMaxLength`` / ``@arrayMinLength``
"""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""

Three functions exist for asserting the length of regular numerically indexed JSON arrays, ``@arrayLength``, ``@arrayMaxLength`` and ``@arrayMinLength``. Given the following response body:

.. code-block:: json

    {
      "items":
      [
        "foo",
        "bar",
        "foobar",
        "barfoo",
        123
      ]
    }

one can assert the exact length using ``@arrayLength``:

.. code-block:: gherkin

    Then the response body contains JSON:
        """
        {"items": "@arrayLength(5)"}
        """

or use the relative length matchers:

.. code-block:: gherkin

    Then the response body contains JSON:
        """
        {"items": "@arrayMaxLength(10)"}
        """
    And the response body contains JSON:
        """
        {"items": "@arrayMinLength(3)"}
        """

Variable type - ``@variableType``
"""""""""""""""""""""""""""""""""

To be able to assert the variable type of specific values, the ``@variableType`` function can be used. The following types can be asserted:

* ``boolean`` / ``bool``
* ``integer`` / ``int``
* ``double`` / ``float``
* ``string``
* ``array``
* ``object``
* ``null``
* ``scalar``

Given the following response:

.. code-block:: json

    {
      "boolean value": true,
      "int value": 123,
      "double value": 1.23,
      "string value": "some string",
      "array value": [1, 2, 3],
      "object value": {"foo": "bar"},
      "null value": null,
      "scalar value": 3.1416
    }

the type of the values can be asserted like this:

.. code-block:: gherkin

    Then the response body contains JSON:
        """
        {
          "boolean value": "@variableType(boolean)",
          "int value": "@variableType(integer)",
          "double value": "@variableType(double)",
          "string value": "@variableType(string)",
          "array value": "@variableType(array)",
          "object value": "@variableType(object)",
          "null value": "@variableType(null)",
          "scalar value": "@variableType(scalar)"
        }
        """

The ``boolean``, ``integer`` and ``double`` functions can also be expressed using ``bool``, ``int`` and ``float`` respectively. There is no difference in the actual validation being executed.

For the ``@variableType(scalar)`` assertion refer to the `is_scalar function <http://php.net/is_scalar>`_ in the PHP manual as to what is considered to be a scalar.

Regular expression matching - ``@regExp``
"""""""""""""""""""""""""""""""""""""""""

To use regular expressions to match values, the ``@regExp`` function exists, that takes a regular expression as an argument, complete with delimiters and optional modifiers. Example:

.. code-block:: gherkin

    Then the response body contains JSON:
        """
        {
          "foo": "@regExp(/(some|expression)/i)",
          "bar":
          {
            "baz": "@regExp(/[0-9]+/)"
          }
        }
        """

This can be used to match variables of type ``string``, ``integer`` and ``float``/``double`` only, and the value that is matched will be cast to a string before doing the match. Refer to the `PHP manual <http://php.net/pcre>`_ regarding how regular expressions work in PHP.

Match specific keys in a numerically indexed array - ``<key>[<index>]``
"""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""""

If you need to verify an element at a specific index within a numerically indexed array, use the ``key[<index>]`` notation as the key, and not the regular field name. Consider the following response body:

.. code-block:: json

    {
      "items":
      [
        "foo",
        "bar",
        {
          "some":
          {
            "nested": "object",
            "foo": "bar"
          }
        },
        [1, 2, 3]
      ]
    }

If you need to verify the values, use something like the following step:

.. code-block:: gherkin

    Then the response body contains JSON:
        """
        {
          "items[0]": "foo",
          "items[1]": "@regExp(/(foo|bar|baz)/)",
          "items[2]":
          {
            "some":
            {
              "foo": "@regExp(/ba(r|z)/)"
            }
          },
          "items[3]": "@arrayLength(3)"
        }
        """

If the response body contains a numerical array as the root node, you will need to use a special syntax for validation. Consider the following response body:

.. code-block:: json

    [
      "foo",
      123,
      {
        "foo": "bar"
      },
      "bar",
      [1, 2, 3]
    ]

To validate this, use the following step:

.. code-block:: gherkin

    Then the response body contains JSON:
        """
        {
          "[0]": "foo",
          "[1]": 123,
          "[2]":
          {
            "foo": "bar"
          },
          "[3]": "@regExp(/bar/)",
          "[4]": "@arrayLength(3)"
        }
        """

Numeric comparison - ``@gt`` / ``@lt``
""""""""""""""""""""""""""""""""""""""

To verify that a numeric value is greater than or less than a value, the ``@gt`` and ``@lt`` functions can be used respectively. Given the following response body:

.. code-block:: json

    {
      "some-int": 123,
      "some-double": 1.23,
      "some-string": "123"
    }

one can compare the numeric values using:

.. code-block:: gherkin

    Then the response body contains JSON:
        """
        {
          "some-int": "@gt(120)",
          "some-double": "@gt(1.20)",
          "some-string": "@gt(120)"
        }
        """
    And the response body contains JSON:
        """
        {
          "some-int": "@lt(125)",
          "some-double": "@lt(1.25)",
          "some-string": "@lt(125)"
        }
        """

.. _jwt-custom-matcher:

JWT token matching - ``@jwt``
"""""""""""""""""""""""""""""

To verify a JWT in the response body the ``@jwt()`` custom matcher function can be used. The argument it takes is the name of a JWT token registered with the :ref:`given-the-response-body-contains-a-jwt` step earlier in the scenario.

Given the following response body:

.. code-block:: json

    {
      "value": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyIjoiU29tZSB1c2VyIn0.DsGGNmDD-PBnwMLiQxeSHDGmKBSdP0lSmWuaiwSxfQE"
    }

one can validate the JWT using a combination of two steps:

.. code-block:: gherkin

    # Register the JWT
    Given the response body contains a JWT identified by "my JWT", signed with "secret":
        """
        {
            "user": "Some user"
        }
        """

    # Other steps ...

    # After the request has been made, one can match the JWT in the response
    And the response body contains JSON:
        """
        {
          "value": "@jwt(my JWT)"
        }
        """
