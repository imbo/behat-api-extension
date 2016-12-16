Set up the request
==================

The following steps can be used prior to sending a request.

.. contents:: Available steps
    :local:

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
