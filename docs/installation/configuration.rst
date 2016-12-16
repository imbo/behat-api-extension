Configuration
=============

After you have installed the extension you need to activate it in your ``behat.yml`` file:

.. code-block:: yaml

    default:
        suites:
            default:
                # ...

        extensions:
            Imbo\BehatApiExtension: ~

The following configuration options are available for the extension:

============  ======  =====================  =======================================
Key           Type    Default value          Description
============  ======  =====================  =======================================
``base_uri``  string  http://localhost:8080  Base URI of the application under test.
============  ======  =====================  =======================================
