Send the request
================

After setting up the request it can be sent to the server in a few different ways. Keep in mind that all configuration regarding the request must be done prior to any of the following steps, as they will actually send the request.

.. _when-i-request-path-using-http-method:

When I request ``:path`` using HTTP ``:method``
-----------------------------------------------

``:path`` is relative to the ``base_uri`` configuration option, and ``:method`` is any HTTP method, for instance ``POST`` or ``DELETE``. If ``:path`` starts with a slash, it will be relative to the root of ``base_uri``.

**Examples:**

*Assume that the ``base_uri`` configuration option has been set to ``http://example.com/dir`` in the following examples.*

=====================================================  =====================  ===========  =======================================
Step                                                   ``:path``              ``:method``  Resulting URI
=====================================================  =====================  ===========  =======================================
When I request "``/?foo=bar&bar=foo``"                 ``/?foo=bar&bar=foo``  ``GET``      ``http://example.com/?foo=bar&bar=foo``
When I request "``/some/path``" using HTTP ``DELETE``  ``/some/path``         ``DELETE``   ``http://example.com/some/path``
When I request "``foobar``" using HTTP ``POST``        ``foobar``             ``POST``     ``http://example.com/dir/foobar``
=====================================================  =====================  ===========  =======================================

When I request ``:path``
------------------------

Request ``:path`` using HTTP GET. Shorthand for :ref:`When I request :path using HTTP GET <when-i-request-path-using-http-method>`.

When I request ``:path`` using HTTP ``:method`` with body: ``<PyStringNode>``
-----------------------------------------------------------------------------

This step can be used to attach a body to the request. The same as above applies for ``:path`` and ``:method``.

**Examples:**

.. code-block:: gherkin

    When I request "some/endpoint" using HTTP POST with body:
        """
        some POST body
        """

When I request ``:path`` using HTTP ``:method`` with JSON body: ``<PyStringNode>``
----------------------------------------------------------------------------------

Use this step to send a request to ``:path`` using HTTP method ``:method`` with a JSON body. The ``Content-Type`` request header will be set to ``application/json`` (regardless of whether or not the ``Content-Type`` header has already been set with the :ref:`given-the-header-request-header-is-value` step.


**Examples:**

.. code-block:: gherkin

    When I request "some/endpoint" using HTTP PUT with JSON body:
        """
        {"foo": "bar"}
        """

The step will validate the JSON data before sending the request using this step. If you want to send invalid JSON data to the server, you can do the following:

.. code-block:: gherkin

    Given the "Content-Type" request header is "application/json"
    When I request "some/endpoint" using HTTP POST with body:
        """
        {"some":"invalid":"json"}
        """

When I send ``:filePath`` (as ``:mimeType``) to ``:path`` using HTTP ``:method``
--------------------------------------------------------------------------------

Send the file at ``:filePath`` to ``:path`` using the ``:method`` HTTP method. ``:filePath`` is relative to the working directory unless it's absolute. ``:method`` would typically be ``PUT`` or ``POST`` for this action, but any valid HTTP method can be used. The optional ``:mimeType`` can be added to force the ``Content-Type`` request header. If not specified the extension will try to guess the mime type using available methods.

**Examples:**

============================================================================================  ==================  ===============================  =============  ===========
Step                                                                                          ``:filePath``       ``:mimeType``                    ``:path``      ``:method``
============================================================================================  ==================  ===============================  =============  ===========
When I send "``/some/file.jpg``" to "``/endpoint``" using HTTP ``POST``                       ``/some/file.jpg``  ``image/jpeg`` (guessed)         ``/endpoint``  ``POST``
When I send "``file.bar``" as "``application/foobar``" to "``/endpoint``" using HTTP ``PUT``  ``file.bar``        ``application/foobar`` (forced)  ``/endpoint``  ``PUT``
============================================================================================  ==================  ===============================  =============  ===========
