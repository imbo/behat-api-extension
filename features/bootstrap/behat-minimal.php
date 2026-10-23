<?php declare(strict_types=1);

use Behat\Config\Config;
use Behat\Config\Extension;
use Behat\Config\Formatter\ProgressFormatter;
use Behat\Config\Profile;
use Imbo\BehatApiExtension\ServiceContainer\BehatApiExtension;

return (new Config())
    ->withProfile(
        (new Profile('default'))
            ->withFormatter(new ProgressFormatter(false))
            ->withExtension(new Extension(BehatApiExtension::class, [
                'apiClient' => ['base_uri' => 'http://localhost:8080'],
            ])),
    );
