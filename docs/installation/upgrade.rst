Upgrading
=========

This section will cover breaking changes between major versions to ease upgrading to the latest version.

v2.0.0
------

``v1.*`` contained several ``When`` steps that could configure the request as well as sending it, in the same step. These steps has been removed in ``v2.0.0``, and the extension now requires you to configure all aspects of the request using the ``Given`` steps prior to issuing one of the few ``When`` steps.

.. contents:: Removed / updated steps
    :local:

Given the request body is ``:string``
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

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
^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^

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
