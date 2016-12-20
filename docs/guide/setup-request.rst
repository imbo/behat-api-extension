Set up the request
==================

The following steps can be used prior to sending a request.

.. contents:: Available steps
    :local:

.. _given-i-attach-path-to-the-request-as-partname:

Given I attach ``:path`` to the request as ``:partName``
--------------------------------------------------------

Attach a file to the request (causing a ``multipart/form-data`` request, populating the ``$_FILES`` array on the server). Can be repeated to attach several files. If a specified file does not exist an ``InvalidArgumentException`` exception will be thrown. ``:path`` is relative to the working directory unless it's absolute.

**Examples:**

==========================================================================  =========================  ==================================================
Step                                                                        ``:path``                  Entry in ``$_FILES`` on the server (``:partName``)
==========================================================================  =========================  ==================================================
Given I attach "``/path/to/file.jpg``" to the request as "``file1``"        ``/path/to/file.jpg``      $_FILES['``file1``']
Given I attach "``c:\some\file.jpg``" to the request as "``file2``"         ``c:\some\file.jpg``       $_FILES['``file2``']
Given I attach "``features/some.feature``" to the request as "``feature``"  ``features/some.feature``  $_FILES['``feature``']
==========================================================================  =========================  ==================================================

This step can not be used when sending requests with a request body. Doing so results in an ``InvalidArgumentException`` exception.

Given I am authenticating as ``:username`` with password ``:password``
----------------------------------------------------------------------

Use this step to set up basic authentication to the next request.

**Examples:**

==============================================================  =============  =============
Step                                                            ``:username``  ``:password``
==============================================================  =============  =============
Given I am authenticating as "``foo``" with password "``bar``"  ``foo``        ``bar``
==============================================================  =============  =============

.. _given-the-header-request-header-is-value:

Given the ``:header`` request header is ``:value``
--------------------------------------------------

Set the ``:header`` request header to ``:value``. Can be repeated to set multiple headers or to set the same header multiple times.

Trying to force specific headers to have certain values combined with other steps that ends up modifying request headers (for instance attaching files) can lead to undefined behavior.

**Examples:**

===============================================================  ==============  ====================
Step                                                             ``:header``     ``:value``
===============================================================  ==============  ====================
Given the "``User-Agent``" request header is "``test/1.0``"      ``User-Agent``  ``test/1.0``
Given the "``Accept``" request header is "``application/json``"  ``Accept``      ``application/json``
===============================================================  ==============  ====================

Given the following form parameters are set: ``<TableNode>``
------------------------------------------------------------

This step can be used to set form parameters (as if the request is a ``<form>`` being submitted). A table node must be used to specify which fields / values to send:

.. code-block:: gherkin

    Given the following form parameters are set:
        | name | value |
        | foo  | bar   |
        | bar  | foo   |
        | bar  | bar   |

The first row in the table must contain two values: ``name`` and ``value``. The rows that follows are the fields / values you want to send. This step sets the HTTP method to ``POST`` and the ``Content-Type`` request header to ``application/x-www-form-urlencoded``, unless the step is combined with :ref:`given-i-attach-path-to-the-request-as-partname`, in which case the ``Content-Type`` request header will be set to ``multipart/form-data`` and all the specified fields will be sent as parts in the multipart request.

This step can not be used when sending requests with a request body. Doing so results in an ``InvalidArgumentException`` exception.

Given the request body is ``:string``
-------------------------------------

Set the request body to ``:string``.

**Examples:**

============================================  ================
Step                                          ``:string``
============================================  ================
Given the request body is "``some content``"  ``some content``
============================================  ================

Given the request body contains ``:path``
-----------------------------------------

This step can be used to set the contents of the file at ``:path`` in the request body. If the file does not exist or is not readable the step will fail.

**Examples:**

===================================================  =================
Step                                                 ``:path``
===================================================  =================
Given the request body contains "``/path/to/file``"  ``/path/to/file``
===================================================  =================

The step will figure out the mime type of the file (using `mime_content_type <http://php.net/mime_content_type>`_) and set the ``Content-Type`` request header as well. If you wish to override the mime type you can use the :ref:`given-the-header-request-header-is-value` step **after** setting the request body.
