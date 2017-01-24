Verify server response
======================

After a request has been sent, some steps exist that can be used to verify the response from the server.

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

Then the ``:header`` response header does not exists
----------------------------------------------------

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

* Then the response body is an array of length ``1``
* Then the response body is an array of length ``3``

If the response body does not contain a JSON array, the test will fail.

Then the response body is a JSON array with a length of at least ``:length``
----------------------------------------------------------------------------

Assert that the length of the JSON array in the response body has a length of at least ``:length``.

**Examples:**

* Then the response body is an array with a length of at least ``4``
* Then the response body is an array with a length of at least ``5``

If the response body does not contain a JSON array, the test will fail.

Then the response body is a JSON array with a length of at most ``:length``
---------------------------------------------------------------------------

Assert that the length of the JSON array in the response body has a length of at most ``:length``.

**Examples:**

* Then the response body is an array with a length of at most ``4``
* Then the response body is an array with a length of at most ``5``

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

Then the response body contains JSON: ``<PyStringNode>``
--------------------------------------------------------

Used to recursively match the response body against a JSON blob (used for comparing objects, not regular arrays). The following occurs when using this step:

1. Decode the response body to a native PHP array. An exception will be thrown if the JSON is invalid.
2. Decode the ``<PyStringNode>`` to a native PHP array. An exception will be thrown if the JSON is invalid.
3. Loop through the ``<PyStringNode>`` array, making sure the key => value pairs are present in the response body array, in a recursive fashion.

The ``<PyStringNode>`` can contain regular expressions for matching values or some specific functions for asserting lengths of arrays.

To use regular expressions to match values, simply write the regular expression, complete with delimiters and optional modifiers, enclosed in ``<re>`` and ``</re>``. Example:

.. code-block:: json

    {
        "foo": "<re>/(some|expression)/i</re>",
        "bar": {
            "baz": "<re>/[0-9]+/</re>"
        }
    }

This can be used to match `scalar values <http://php.net/is_scalar>`_ only, and the value will be cast to a string before doing the match.

To assert lengths of arrays, three custom functions can be used: ``@length(num)``, ``@atLeast(num)`` and ``@atMost(num)``. Consider the following response body:

.. code-block:: json

    {
        "items1": [1, 2, 3, 4],
        "items2": [1, 2, 3],
        "items3": [1, 2]
    }

To be able to verify the length of the arrays one can use the following JSON (excluding the comments which are not supported by JSON):

.. code-block:: javascript

    {
        "items1": "@length(3)",  // Fails as the length is 4
        "items2": "@atLeast(3)", // Passes as the length is 3
        "items3": "@atMost(1)"   // Fails as the length is 2
    }

If you need to verify an element at a specific index within an array, use the ``key[<index>]`` notation as the key. Consider the following response body:

.. code-block:: json

    {
        "items": [
            "foo",
            "bar",
            "baz",
            {
                "some":
                {
                    "nested": "object",
                    "foo": "bar"
                }
            }
        ]
    }

If you need to verify the values, use the following JSON:

.. code-block:: javascript

    {
        "items[0]": "foo",                      // Passes, string comparison
        "items[1]": "<re>/(foo|bar|baz)/</re>", // Passes as the expression matches "bar"
        "items[2]": "bar",                      // Fails as the value is baz
        "items[3]":
        {
            "some":
            {
                "foo": "<re>/ba(r|z)/</re>"     // Passes as the expression matches "bar"
            }
        },
        "items[4]": "bar"                       // Throws an OutOfRangeException exception as the index does not exist
    }

If you use the index checking against something that is not a numeric array, the extension will throw an ``InvalidArgumentException`` exception.

You can also assert that values exists in numerically indexed arrays. Consider the following JSON response body:

.. code-block:: json

    {
        "list": [
            1,
            2,
            3,
            "four",
            [1],
            {
                "foo": "bar"
            }
        ]
    }

To assert that one or more of the values exist, use the following:

.. code-block:: json

    {
        "list": [
            3,
            [1],
            {
                "foo": "bar"
            }
        ]
    }

The index is not taken into consideration when comparing, it simply checks if the values specified are present in the list.

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

To validate this, use the following syntax:

.. code-block:: json

    {
        "[0]": "foo",
        "[1]": 123,
        "[2]": {
            "foo": "bar"
        },
        "[3]": "<re>/bar/</re>",
        "[4]": "@length(3)"
    }

This simply refers to the indexes in the root numerical array.
