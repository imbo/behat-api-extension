# Behat API Extension

[![Current build Status](https://secure.travis-ci.org/imbo/behat-api-extension.png)](http://travis-ci.org/imbo/behat-api-extension)

This Behat extension provides an easy way to test JSON-based API's in [Behat 3](http://behat.org). Inspired by [behat/web-api-extension](https://github.com/Behat/WebApiExtension/) and originally written to test the [Imbo API](http://imbo.io).

## Requirements

Behat API extension requires:

* [PHP](http://php.net) 5.6+
* [behat/behat](http://behat.org) 3.0+
* [guzzlehttp/guzzle](http://guzzlephp.org) 6.0+
* [beberlei/assert](https://github.com/beberlei/assert/) 2.1+

## Installation

Install the extension by adding the following to your `composer.json` file:

    {
        "require-dev": {
            "imbo/behat-api-extension": "^1.0"
        }
    }

and then updating your dependencies by issuing `composer update imbo/behat-api-extension`.

After you have installed it you need to activate the extension in your `behat.yml` file:

    default:
        suites:
            default:
                # ...

        extensions:
            Imbo\BehatApiExtension: ~

## Configuration

The following configuration options are available:

Key | Type | Default value | Description
--- | ---- | ------------- | -----------
base_uri | string | http://localhost:8080 | Base URI of the application under test

## Usage

The extension allows you to use the following steps in your features:

    When I request "<url>"
    When I request "<url>" using HTTP <method>

`<url>` is a URL relative to the `base_uri` configuration option, and `<method>` is any HTTP method, for instance `POST` or `DELETE`. The first form uses `HTTP GET`.

    Given the "<header>" request header is "<value>"

Set the `<header>` request header to `<value>`. Can be repeated to make a header appear multiple times.

    Then the response body should be "<content>"

Match the whole response body to `<content>`.

    Then the response code should be <code>

Match the response code to `<code>`.

    Given I am authenticating as "<username>" with password "<password>"

Use this step when the URL you are requesting required basic auth.

## Copyright / License

Copyright (c) 2016, Christer Edvartsen <cogo@starzinger.net>

Licensed under the MIT License

## Community

If you have any questions feel free to join `#imbo` on the Freenode IRC network (`chat.freenode.net`).
