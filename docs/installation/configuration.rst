Configuration
=============

After you have installed the extension you need to activate it in your Behat configuration file (for instance ``behat.yml``):

.. code-block:: yaml

    default:
      suites:
        default:
          # ...

      extensions:
        Imbo\BehatApiExtension: ~
      extensions:
        Imbo\BehatApiExtension:
          apiClient:
            base_uri: http://localhost:8080
     suites:
       default:
         contexts: ['Imbo\BehatApiExtension\Context\ApiContext']        

The following configuration options are required for the extension to work as expected:

======================  ======  =====================  =======================================
Key                     Type    Default value          Description
======================  ======  =====================  =======================================
``apiClient.base_uri``  string  http://localhost:8080  Base URI of the application under test.
======================  ======  =====================  =======================================

It should be noted that everything in the ``apiClient`` configuration array is passed directly to the Guzzle Client instance used internally by the extension.

Example of a configuration file with several configuration entries:

.. code-block:: yaml

    default:
      suites:
        default:
          # ...

      extensions:
        Imbo\BehatApiExtension:
          apiClient:
            base_uri: http://localhost:8080
            timeout: 5.0
            verify: false

Refer to the `Guzzle documentation <http://docs.guzzlephp.org/en/stable/>`_ for available configuration options for the Guzzle client.
