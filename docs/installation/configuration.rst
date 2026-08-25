Configuration
=============

After you have installed the extension you need to activate it in your Behat configuration file (for instance ``behat.dist.php``):

.. code-block:: php

    <?php declare(strict_types=1);

    use Behat\Config\Config;
    use Behat\Config\Extension;
    use Behat\Config\Profile;
    use Imbo\BehatApiExtension\ServiceContainer\BehatApiExtension;

    $config = new Config();
    $profile = new Profile('default');
    $extension = new Extension(BehatApiExtension::class, [
        'apiClient' => ['base_uri' => '...'],
    ]);

    return $config->withProfile($profile->withExtension($extension));


The following configuration options are required for the extension to work as expected:

======================  ======  =====================  =======================================
Key                     Type    Default value          Description
======================  ======  =====================  =======================================
``apiClient.base_uri``  string  http://localhost:8080  Base URI of the application under test.
======================  ======  =====================  =======================================

It should be noted that everything in the ``apiClient`` configuration array is passed directly to the Guzzle Client instance used internally by the extension.

Refer to the `Guzzle documentation <https://docs.guzzle.org/>`_ for available configuration options for the Guzzle client.

Refer to the `Behat documentation <https://docs.behat.org/>`_ for more information on configuring Behat.
