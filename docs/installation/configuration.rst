Configuration
=============

After you have installed the extension you need to activate it and register its context in your Behat configuration file (for instance ``behat.dist.php``):

.. code-block:: php

    <?php declare(strict_types=1);

    use Behat\Config\Config;
    use Behat\Config\Extension;
    use Behat\Config\Profile;
    use Behat\Config\Suite;
    use Imbo\BehatApiExtension\Context\ApiContext;
    use Imbo\BehatApiExtension\ServiceContainer\BehatApiExtension;

    $config = new Config();
    $profile = new Profile('default');
    $extension = new Extension(BehatApiExtension::class, [
        'apiClient' => ['base_uri' => '...'],
    ]);

    $suite = (new Suite('default'))->withContexts(ApiContext::class);

    return $config->withProfile(
        $profile->withExtension($extension)->withSuite($suite)
    );

This registers the steps provided by ``ApiContext``. If you have a custom context that extends ``ApiContext``, register that class instead. Refer to :doc:`../guide/extending-the-extension` for examples.

The following configuration options are required for the extension to work as expected:

======================  ======  =============  =======================================
Key                     Type    Default value  Description
======================  ======  =============  =======================================
``apiClient.base_uri``  string  None           Base URI of the application under test.
======================  ======  =============  =======================================

There is no default base URI. Configure ``apiClient.base_uri`` explicitly for the application under test.

It should be noted that everything in the ``apiClient`` configuration array is passed directly to the Guzzle Client instance used internally by the extension.

Refer to the `Guzzle documentation <https://docs.guzzle.org/>`_ for available configuration options for the Guzzle client.

Refer to the `Behat documentation <https://docs.behat.org/>`_ for more information on configuring Behat.
