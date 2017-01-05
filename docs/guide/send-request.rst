Send the request
================

After setting up the request it can be sent to the server in a few different ways. Keep in mind that all configuration regarding the request must be done prior to any of the following steps, as they will actually send the request.

When I request ``:path``
------------------------

Request ``:path`` using HTTP GET. Shorthand for :ref:`When I request :path using HTTP GET <when-i-request-path-using-http-method>`.

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
