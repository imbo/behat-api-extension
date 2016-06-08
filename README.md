# Behat API Extension

[![Current build Status](https://secure.travis-ci.org/imbo/behat-api-extension.png)](http://travis-ci.org/imbo/behat-api-extension)

**This extension is a work in progress. The steps mentioned below will probably be updated along the way until a stable release has been made, so expect things to change without warning.**

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
            "imbo/behat-api-extension": "^1.0@dev"
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

### Requests

#### `Given I am authenticating as "<username>" with password "<password>"`

Use this step when the URL you are requesting required basic auth. Must be used before any of the `When I request` steps.

#### `Given the "<header>" request header is "<value>"`

Set the `<header>` request header to `<value>`. Can be repeated to set multiple headers or to set the same header multiple times. Must be used before any of the `When I request` steps.

#### `When I request "<url>"`
#### `When I request "<url>" using HTTP <method>`
#### `When I request "<url>" using HTTP <method> with body: <PyStringNode>`
#### `When I request "<url>" using HTTP <method> with JSON body: <PyStringNode>`

`<url>` is a URL relative to the `base_uri` configuration option, and `<method>` is any HTTP method, for instance `POST` or `DELETE`. The first form uses `HTTP GET`. The last two forms should be used with a `PyStringNode`, for instance:

    When I request "/some-path" using HTTP POST with body:
        """
        {"some":"json"}
        """

The last form adds a `Content-Type: application/json` request header automatically.

Using any of these steps actually issues the request, so authentication details and request headers must be used prior to this.

### Responses

#### `Then the response code should be <code>`

Match the response code to `<code>`.

#### `Then the response code means <group>`

Match the response code to a group. Allowed groups and their ranges are:

| Group | Min | Max |
| ----- | --- | --- |
| `informational` | 100 | 199 |
| `success` | 200 | 299 |
| `redirection` | 300 | 399 |
| `client error` | 400 | 499 |
| `server error` | 500 | 599 |

#### `Then the response body should contain JSON key "<key>"`

Make sure a key exists in the JSON object in the response.

#### `Then the response body should contain JSON keys: <TableNode>`

Used with a `TableNode`, for instance:

    Then the response body should contain JSON keys:
        | key    |
        | foobar |
        | barfoo |

The first line in the table is special and must be named `key`.

#### `Then the response body should contain JSON: <PyStringNode>`

Used to match key/value pairs in the response body. Given the following response body:

```javascript
{
    "site": "site url",
    "source": "source url",
    "docs": "docs url"
}
```

the following steps would pass:

```
Then the response body should contain JSON:
    """
    {"site":"site url"}
    """
```

```
Then the response body should contain JSON:
    """
    {"site":"site url","source":"source url"}
    """
```

and the following would not:

```
Then the response body should contain JSON:
    """
    {"foo":"bar"}
    """
```

#### `Then the response body should be "<content>"`

Match the whole response body to `<content>`.

## Copyright / License

Copyright (c) 2016, Christer Edvartsen <cogo@starzinger.net>

Licensed under the MIT License

## Community

If you have any questions feel free to join `#imbo` on the Freenode IRC network (`chat.freenode.net`).
