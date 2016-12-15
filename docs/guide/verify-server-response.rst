Verify server response
======================

After a request has been sent, some steps exist that can be used to verify the response from the server. All steps that matches response content assumes JSON-data in the response body unless noted otherwise.

Then the response code is (not) ``:code``
-----------------------------------------

Match the response code to ``:code``. If the optional ``not`` is added, the response should **not** match the response code.

**Examples:**

=====================================  =========  ===============  ===============  ===============
Step                                   ``:code``  Matches ``200``  Matches ``304``  Matches ``404``
=====================================  =========  ===============  ===============  ===============
Then the response code is ``200``      ``200``    Yes              No               No
Then the response code is ``404``      ``404``    No               No               Yes
Then the response code is not ``304``  ``304``    Yes              No               Yes
=====================================  =========  ===============  ===============  ===============

Then the response reason phrase is ``:phrase``
----------------------------------------------

Match the response reason phrase to ``:phrase``.

*Assume that these steps match a response with "200 OK" as a status line.*

**Examples:**

===========================================  ===========  ============
Step                                         ``:phrase``  Test passes?
===========================================  ===========  ============
Then the response reason phrase is "``OK``"  ``OK``       Yes
Then the response reason phrase is "``ok``"  ``ok``       No
===========================================  ===========  ============

Then the response status line is ``:line``
------------------------------------------

Match the response status line to ``:line``.

*Assume that these steps match a response with "200 OK" as a status line.*

**Examples:**

=============================================  ==========  ============
Step                                           ``:line``   Test passes?
=============================================  ==========  ============
Then the response status line is "``200``"     ``200``     No
Then the response status line is "``200 OK``"  ``200 OK``  Yes
=============================================  ==========  ============

Then the response is (not) ``:group``
-------------------------------------

Match the response code to a group. If the optional ``not`` is added, the response should **not** be in the specified group.

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

===========================================  =================  ================================
Step                                         ``:group``         Response code range that matches
===========================================  =================  ================================
Then the response is "``informational``"     ``informational``  100 to 199
Then the response is "``client error``"      ``client error``   400 to 499
Then the response is not "``client error``"  ``client error``   100 to 399 and 500 to 599
===========================================  =================  ================================

Then the ``:header`` response header (does not) exist(s)
--------------------------------------------------------

This step can be used to assert that the ``:header`` response header exists, or not (if used with the optional ``does not`` part). The value of ``:header`` is case-insensitive.

**Examples:**

*Assume that these response headers exist in the following examples:*

* *Content-Length: 186*

============================================================  ==================  ============
Step                                                          ``:header``         Test passes?
============================================================  ==================  ============
Then the "``Vary``" response header exists                    ``Vary``            No
Then the "``vary``" response header does not exist            ``vary``            Yes
Then the "``Content-Length``" response header exists          ``Content-Length``  Yes
Then the "``content-length``" response header does not exist  ``content-length``  No
============================================================  ==================  ============

Then the ``:header`` response header is|matches ``:value``
----------------------------------------------------------

This step can be used to verify the value of one or more response headers.

The step supports two different comparison modes, ``is`` and ``matches``. ``is`` will compare the values using string comparison, and when ``matches`` is used, the ``:value`` must be a valid regular expression, complete with delimiters and optional modifiers.

**Examples:**

*Assume that these response headers exist in the following examples:*

* *Content-Length: 14327*
* *X-Foo: foo, bar*

====================================================================  ==================  =================  ==================  ==============
Step                                                                  ``:header``         ``:value``         Mode                Matches header
====================================================================  ==================  =================  ==================  ==============
Then the "``Content-Length``" response header is "``15000``"          ``Content-Length``  ``15000``          Comparison          No
Then the "``content-length``" response header matches "``/[0-9]+/``"  ``content-length``  ``/[0-9]+/``       Regular expression  Yes
Then the "``x-foo``" response header matches "``/(FOO|BAR)/i``"       ``x-foo``           ``/(FOO|BAR)/i``   Regular expression  Yes
Then the "``X-FOO``" response header matches "``/^(foo|bar)$/``"      ``X-FOO``           ``/^(foo|bar)$/``  Regular expression  No
Then the "``X-foo``" response header is "``foo, bar``"                ``X-foo``           ``foo, bar``       Comparison          Yes
====================================================================  ==================  =================  ==================  ==============

For more information regarding regular expressions and the usage of modifiers, `refer to the manual <http://php.net/pcre>`_.

.. _then-the-response-body-is-an-array-of-length:

Then the response body is an array of length ``:length``
--------------------------------------------------------

This step can be used to verify the exact length of a JSON array in the response body.

**Examples:**

*Assume that for the examples below, the response body is ``[1, 2, 3]``.*

==================================================  ===========  ============
Step                                                ``:length``  Test passes?
==================================================  ===========  ============
Then the response body is an array of length ``1``  ``1``        No
Then the response body is an array of length ``3``  ``3``        Yes
==================================================  ===========  ============

If the response body does not contain a JSON array, an ``InvalidArgumentException`` exception will be thrown.

Then the response body is an empty array
----------------------------------------

This is an alias of :ref:`Then the response body is an array of length 0 <then-the-response-body-is-an-array-of-length>`.

Then the response body is an empty object
-----------------------------------------

Assert that the response body is an empty JSON object (`{}`).

Then the response body is an array with a length of at least|most ``:length``
-----------------------------------------------------------------------------

This step can be used to verify the length of an array, without having to be exact.

**Examples:**

*Assume that for the examples below, the response body is [1, 2, 3, 4, 5].*

==================================================================  ===========  ============
Step                                                                ``:length``  Test passes?
==================================================================  ===========  ============
Then the response body is an array with a length of at most ``4``   ``4``        No
Then the response body is an array with a length of at least ``4``  ``4``        Yes
Then the response body is an array with a length of at most ``5``   ``5``        Yes
Then the response body is an array with a length of at least ``5``  ``5``        Yes
Then the response body is an array with a length of at most ``6``   ``6``        Yes
Then the response body is an array with a length of at least ``6``  ``6``        No
==================================================================  ===========  ============

Then the response body is: ``<PyStringNode>``
---------------------------------------------

Compare the response body to the text found in the ``<PyStringNode>`` using string comparison.

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

Match the response body to the regular expression found in the content of ``<PyStringNode>``. The expression must be a valid regular expression, including delimiters and optional modifiers.

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

Then the response body contains: ``<PyStringNode>``
---------------------------------------------------

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
