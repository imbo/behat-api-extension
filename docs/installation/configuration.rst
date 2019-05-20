Configuration
=============

After you have installed the extension you need to activate it in your Behat configuration file (for instance ``behat.yml``):

.. code-block:: yaml

    default:
        suites:
            default:
                contexts:
                    - Imbo\BehatApiExtension\Context\ApiContext
                    # other contexts like...
                    #- FeatureContext:
                    #   kernel: '@kernel'


        extensions:
            Imbo\BehatApiExtension: ~

The following configuration options are required for the extension to work as expected:

======================  ======  =====================  =====================================================================================
Key                     Type    Default value          Description
======================  ======  =====================  =====================================================================================
``apiClient.base_uri``  string  http://localhost:8080  Base URI of the application under test. Must be connectable for the tests to execute.
======================  ======  =====================  =====================================================================================

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

You cant set `contexts` in config file if your `FeatrureContext` extends `ApiContext`. Docs of extending is avaliable  [there](https://behat-api-extension.readthedocs.io/en/latest/guide/extending-the-extension.html)
