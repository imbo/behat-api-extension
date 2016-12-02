# Behat API Extension

[![Current build Status](https://secure.travis-ci.org/imbo/behat-api-extension.png)](http://travis-ci.org/imbo/behat-api-extension)
[![Latest Stable Version](https://poser.pugx.org/imbo/behat-api-extension/version)](https://packagist.org/packages/imbo/behat-api-extension)
[![License](https://poser.pugx.org/imbo/behat-api-extension/license)](https://packagist.org/packages/imbo/behat-api-extension)

This Behat extension provides an easy way to test JSON-based API's in [Behat 3](http://behat.org). Inspired by [behat/web-api-extension](https://github.com/Behat/WebApiExtension/) and originally written to test the [Imbo API](http://imbo.io).

## Requirements

Behat API extension requires:

* [PHP](http://php.net) 5.6+
* [behat/behat](http://behat.org) 3.0+
* [guzzlehttp/guzzle](http://guzzlephp.org) 6.0+
* [beberlei/assert](https://github.com/beberlei/assert/) 2.1+

## Installation

Install the extension by adding the following to your `composer.json` file:

```json
{
    "require-dev": {
        "imbo/behat-api-extension": "^1.0"
    }
}
```

and then updating your dependencies by issuing `composer update imbo/behat-api-extension`.

After you have installed it you need to activate the extension in your `behat.yml` file:

```yml
default:
    suites:
        default:
            # ...

    extensions:
        Imbo\BehatApiExtension: ~
```

## Configuration

The following configuration options are available for the extension:

| Key      | Type   | Default value         | Description                            |
| -------- | ------ | --------------------- | -------------------------------------- |
| base_uri | string | http://localhost:8080 | Base URI of the application under test |

## Usage

**TL;DR Here is a bunch of steps you can use in your feature files:**

```gherkin
Given I attach :path to the request as :partName
Given I am authenticating as :username with password :password
Given the :header request header is :value
Given the following form parameters are set: <TableNode>

When I request :path
When I request :path using HTTP :method
When I request :path using HTTP :method with body: <PyStringNode>
When I request :path using HTTP :method with JSON body: <PyStringNode>
When I send :filePath to :path using HTTP :method
When I send :filePath as :mimeType to :path using HTTP :method

Then the response code is :code
Then the response code is not :code
Then the response reason phrase is :phrase
Then the response is :group
Then the response is not :group
Then the :header response header exists
Then the :header response header does not exist
Then the :header response header is :value
Then the :header response header matches :pattern
Then the response body is an empty array
Then the response body is an array of length :length
Then the response body is an array with a length of at least :length
Then the response body is an array with a length of at most :length
Then the response body is: <PyStringNode>
Then the response body matches: <PyStringNode>
Then the response body contains: <PyStringNode>
```

### Set up the request

The following steps can be used prior to sending a request.

#### Given I attach `:path` to the request as `:partName`

Attach a file to the request (causing a `multipart/form-data` request, populating the `$_FILES` array on the server). Can be repeated to attach several files. If a specified file does not exist an `InvalidArgumentException` exception will be thrown. `:path` is relative to the working directory unless it's absolute.

**Examples:**

| Step                                                                  | :path                   | Entry in `$_FILES` on the server (:partName) |
| --------------------------------------------------------------------- | ----------------------- | -------------------------------------------- |
| Given I attach "`/path/to/file.jpg`" to the request as `file1`        | `/path/to/file.jpg`     | $\_FILES['`file1`']                          |
| Given I attach "`c:\some\file.jpg`" to the request as `file2`         | `c:\some\file.jpg`      | $\_FILES['`file2`']                          |
| Given I attach "`features/some.feature`" to the request as `feature`  | `features/some.feature` | $\_FILES['`feature`']                        |

This step can not be used when sending requests with a request body. Doing so results in an `InvalidArgumentException` exception.

#### Given I am authenticating as `:username` with password `:password`

Use this step when the URL you are requesting requires basic auth.

**Examples:**

| Step                                                               | :username | :password |
| ------------------------------------------------------------------ | --------- | --------- |
| Given I am authenticating as `foo` with password `bar`             | `foo`     | `bar`     |
| Given I am authenticating as "`foo bar`" with password '`bar foo`' | `foo bar` | `bar foo` |
| Given I am authenticating as '`"foo"`' with password "`'bar'`"     | `"foo"`   | `'bar'`   |

#### Given the `:header` request header is `:value`

Set the `:header` request header to `:value`. Can be repeated to set multiple headers or to set the same header multiple times.

Trying to force specific headers to have certain values combined with other steps that ends up modifying request headers (for instance attaching files) can lead to undefined behaviour.

**Examples:**

| Step                                                                                       | :header      | :value            |
| ------------------------------------------------------------------------------------------ | ------------ | ----------------- |
| Given the "`User-Agent`" request header is "`test/1.0`"                                    | `User-Agent` | `test/1.0`        |
| Given the "`X-Foo`" request header is `Bar`<br>Given the "`X-Foo`" request header is `Baz` | `X-Foo`      | `Bar, Baz`        |
| Given the `Accept` request header is "`application/json`"                                  | `Accept`     | `application/json`|

#### Given the following form parameters are set: `<TableNode>`

This step can be used to set form parameters (as if the request is a `<form>` being submitted. A table node must be used to specify which fields / values to send:

```gherkin
Given the following form parameters are set:
    | name | value |
    | foo  | bar   |
    | bar  | foo   |
    | bar  | bar   |
```

The first row in the table must contain two values: `name` and `value`. The rows that follows are the fields / values you want to send. This step sets the HTTP method to `POST` and the `Content-Type` request header to `application/x-www-form-urlencoded`, unless the step is combined with `Given I attach :path to the request as :partName`, in which case the `Content-Type` will be `multipart/form-data` and all the specified fields will be sent as parts in the multipart request.

This step can not be used when sending requests with a request body. Doing so results in an `InvalidArgumentException` exception.

### Send the request

After setting up the request it can be sent to the server in a few different ways. Keep in mind that all configuration regarding the request must be set prior to any of these steps, as they will actually send the request.

#### When I request `:path` (using HTTP `:method`)

`:path` is relative to the `base_uri` configuration option, and `:method` is any HTTP method, for instance `POST` or `DELETE`. If the last part of the step is omitted, `HTTP GET` will be used. If the `:path` starts with a slash, it will be relative to the root of `base_uri`.

**Examples:**

*Assume that the `base_uri` configuration option has been set to `http://example.com/dir` in the following examples.*

| Step                                               | :path               | :method  | Resulting URL                         |
| -------------------------------------------------- | ------------------- | -------- | ------------------------------------- |
| When I request "`/?foo=bar&bar=foo`"               | `/?foo=bar&bar=foo` | `GET`    | `http://example.com/?foo=bar&bar=foo` |
| When I request "`/some/path`" using HTTP `DELETE`  | `/some/path`        | `DELETE` | `http://example.com/some/path`        |
| When I request `foobar` using HTTP `POST`          | `foobar`            | `POST`   | `http://example.com/dir/foobar`       |

#### When I request `:path` using HTTP `:method` with (JSON) body: `<PyStringNode>`

This step can be used to attach a body to the request. The same as above applies for `:path` and `:method`. If the `JSON` part is added to the step the `Content-Type` request header will be set to `application/json` (regardless of whether or not the `Content-Type` header has already been set with the `Given the :header request header is :value` step described above).

**Examples:**

```gherkin
When I request "some/endpoint" using HTTP POST with body:
    """
    some POST body
    """
```

and with the optional `JSON` identifier, that sets the `Content-Type` request header to `application/json`:

```gherkin
When I request "some/endpoint" using HTTP PUT with JSON body:
    """
    {"foo": "bar"}
    """
```

The extension will validate the JSON data before sending the request using this step. If you want to send invalid JSON data to the server, you can do the following:

```gherkin
Given the "Content-Type" request header is "application/json"
When I request "some/endpoint" using HTTP POST with body:
    """
    {"some":"invalid":"json"}
    """
```

#### When I send `:filePath` (as `:mimeType`) to `:path` using HTTP `:method`

Send the file at `:filePath` to `:path` using the `:method` HTTP method. `:filePath` is relative to the working directory unless it's absolute. `:method` would typically be `PUT` or `POST` for this action, but any valid HTTP method can be used. The optional `:mimeType` can be added to force the `Content-Type` request header. If not specified the extension will try to guess the mime type using available methods.

**Examples:**

| Step                                                                                 | :filePath        | :mimeType                     | :path       | :method |
| ------------------------------------------------------------------------------------ | ---------------- | ----------------------------- | ----------- | ------- |
| When I send "`/some/file.jpg`" to "`/endpoint`" using HTTP `POST`                    | `/some/file.jpg` | `image/jpeg` (guessed)        | `/endpoint` | `POST`  |
| When I send "`file.bar`" as "`application/foobar`" to "`/endpoint`" using HTTP `PUT` | `file.bar`       | `application/foobar` (forced) | `/endpoint` | `PUT`   |

### Verify server response

After a request has been sent, some steps exist that can be used to verify the response from the server. All steps that matches response content assumes JSON-data in the response body unless noted otherwise.

#### Then the response code is (not) `:code`

Match the response code to `:code`. If the optional `not` is added, the response should **not** match the response code.

**Examples:**

| Step                                | :code | Matches `200` | Matches `304` | Matches `404` |
| ----------------------------------- | ----- | ------------- | ------------- | ------------- |
| Then the response code is `200`     | `200` | Yes           | No            | No            |
| Then the response code is `404`     | `404` | No            | No            | Yes           |
| Then the response code is not `304` | `304` | Yes           | No            | Yes           |

#### Then the response reason phrase is `:phrase`

Match the response reason phrase to `:phrase`.

*Assume that these steps match a response with `200 OK` as a status line.*

**Examples:**

| Step                                      | :phrase | Test passes? |
| ----------------------------------------- | ------- | ------------ |
| Then the response reason phrase is "`OK`" | `OK`    | Yes          |
| Then the response reason phrase is "`ok`" | `ok`    | No           |

#### Then the response is (not) `:group`

Match the response code to a group. If the optional `not` is added, the response should **not** be in the specified group.

Allowed groups and their ranges are:

| Group           | Response code range |
| --------------- | ------------------- |
| `informational` | 100 to 199          |
| `success`       | 200 to 299          |
| `redirection`   | 300 to 399          |
| `client error`  | 400 to 499          |
| `server error`  | 500 to 599          |

**Examples:**

| Step                                      | :group          | Response code range that matches |
| ----------------------------------------- | --------------- | -------------------------------- |
| Then the response is `informational`      | `informational` | 100 to 199                       |
| Then the response is "`client error`"     | `client error`  | 400 to 499                       |
| Then the response is not "`client error`" | `client error`  | 100 to 399 and 500 to 599        |

#### Then the `:header` response header (does not) exist(s)

This step can be used to assert that the `:header` response header exists, or not (if used with the optional `does not` part). The value of `:header` is case-insensitive.

**Examples:**

*Assume that these response headers exist in the following examples:*

* *Content-Length: 186*

| Step                                                       | :header          | Test passes? |
| ---------------------------------------------------------- | ---------------- | ------------ |
| Then the `Vary` response header exists                     | `Vary`           | No           |
| Then the `vary` response header does not exist             | `vary`           | Yes          |
| Then the "`Content-Length`" response header exists         | `Content-Length` | Yes          |
| Then the "`content-length`" response header does not exist | `content-length` | No           |

#### Then the `:header` response header is|matches `:value`

This step can be used to verify the value of one or more response headers.

The step supports two different comparison modes, `is` and `matches`. `is` will compare the values using string comparison, and when `matches` is used, the `:value` must be a valid regular expression, complete with delimiters and optional modifiers.

**Examples:**

*Assume that these response headers exist in the following examples:*

* *Content-Length: 14327*
* *X-Foo: foo, bar*

| Step                                                                         | :header          | :value                          | Mode               | Matches header |
| ---------------------------------------------------------------------------- | ---------------- | ------------------------------- | ------------------ | -------------- |
| Then the "`Content-Length`" response header is `15000`                       | `Content-Length` | `15000`                         | Comparison         | No             |
| Then the "`content-length`" response header matches "`/[0-9]+/`"             | `content-length` | `/[0-9]+/`                      | Regular expression | Yes            |
| Then the "`x-foo`" response header matches "<code>/(FOO&#124;BAR)/i</code>"  | `x-foo`          | <code>/(FOO&#124;BAR)/i</code>  | Regular expression | Yes            |
| Then the "`X-FOO`" response header matches "<code>/^(foo&#124;bar)$/</code>" | `X-FOO`          | <code>/^(foo&#124;bar)$/</code> | Regular expression | No             |
| Then the "`X-foo`" response header is "`foo, bar`"                           | `X-foo`          | `foo, bar`                      | Comparison         | Yes            |

For more information regarding regular expressions and the usage of modifiers, [refer to the manual](http://php.net/pcre).

#### Then the response body is an empty array

This is the same as `Then the response body is an array of length 0`.

#### Then the response body is an array of length `:length`

This step can be used to verify the exact length of a JSON array in the response body.

**Examples:**

*Assume that for the examples below, the response body is `[1, 2, 3]`.*

| Step                                             | :length | Test passes? |
| ------------------------------------------------ | ------- | ------------ |
| Then the response body is an empty array         | `0`     | No           |
| Then the response body is an array of length `1` | `1`     | No           |
| Then the response body is an array of length `3` | `3`     | Yes          |

If the response body does not contain a JSON array, an `InvalidArgumentException` exception will be thrown.

#### Then the response body is an array with a length of at least|most `:length`

This step can be used to verify the length of an array, without having to be exact.

**Examples:**

*Assume that for the examples below, the response body is `[1, 2, 3, 4, 5]`.*

| Step                                                             | :length | Test passes? |
| ---------------------------------------------------------------- | ------- | ------------ |
| Then the response body is an array with a length of at most `4`  | `4`     | No           |
| Then the response body is an array with a length of at least `4` | `4`     | Yes          |
| Then the response body is an array with a length of at most `5`  | `5`     | Yes          |
| Then the response body is an array with a length of at least `5` | `5`     | Yes          |
| Then the response body is an array with a length of at most `6`  | `6`     | Yes          |
| Then the response body is an array with a length of at least `6` | `6`     | No           |

#### Then the response body is: `<PyStringNode>`

Compare the response body to the text found in the `<PyStringNode>` using string comparison.

**Examples:**

*Assume that for the examples below, the response body is `{"foo":"bar"}`.*

| Step                                                        | PyStringNode     | Matches |
| ----------------------------------------------------------- | ---------------- | ------- |
| Then the response body is:<br>"""<br>`{"foo":"bar"}`<br>""" | `{"foo":"bar"}`  | Yes     |
| Then the response body is:<br>"""<br>`foo`<br>"""           | `foo`            | No      |

#### Then the response body matches: `<PyStringNode>`

Match the response body to the regular expression found in the content of `<PyStringNode>`. The expression must be a valid regular expression, including delimiters and optional modifieres.

**Examples:**

*Assume that for the examples below, the response body is `{"foo": "bar"}`.*

| Step                                                                    | PyStringNode           | Matches response body |
| ----------------------------------------------------------------------- | ---------------------- | --------------------- |
| Then the response body matches:<br>"""<br>`/^{"FOO": ?"BAR"}$/i`<br>""" | `/^{"FOO": ?"BAR"}$/i` | Yes                   |
| Then the response body matches:<br>"""<br>`/foo/`<br>"""                | `/foo/`                | Yes                   |
| Then the response body matches:<br>"""<br>`/^foo$/`<br>"""              | `/^foo$/`              | No                    |

#### Then the response body contains: `<PyStringNode>`

Used to recursively match the response body against a JSON blob (used for comparing objects, not regular arrays). The following occurs when using this step:

1. Decode the response body to a native PHP array. An exception will be thrown if the JSON is invalid.
2. Decode the `<PyStringNode>` to a native PHP array. An exception will be thrown if the JSON is invalid.
3. Loop through the `<PyStringNode>` array, making sure the key => value pairs are present in the response body array, in a recursive fashion.

The `<PyStringNode>` can contain regular expressions for matching values or some specific functions for asserting lengths of arrays.

To use regular expressions to match values, simply write the regular expression, complete with delimiters and optional modifiers, enclosed in `<re>` and `</re>`. Example:

```json
{
    "foo": "<re>/(some|expression)/i</re>",
    "bar": {
        "baz": "<re>/[0-9]+/</re>"
    }
}
```

This can be used to match [scalar values](http://php.net/is_scalar) only, and the value will be cast to a string before doing the match.

To assert lengths of arrays, three custom functions can be used: `@length(num)`, `@atLeast(num)` and `@atMost(num)`. Consider the following response body:

```json
{
    "items1": [1, 2, 3, 4],
    "items2": [1, 2, 3],
    "items3": [1, 2]
}
```

To be able to verify the length of the arrays one can use the following JSON (excluding the comments which are not supported by JSON):

```javascript
{
    "items1": "@length(3)",  // Fails as the length is 4
    "items2": "@atLeast(3)", // Passes as the length is 3
    "items3": "@atMost(1)"   // Fails as the length is 2
}
```

If you need to verify an element at a specific index within an array, use the `key[<index>]` notation as the key. Consider the following response body:

```json
{
    "items": [
        "foo",
        "bar",
        "baz",
        {
            "some":
            {
                "nested": "object",
                "foo": "bar"
            }
        }
    ]
}
```

If you need to verify the values, use the following JSON:

```javascript
{
    "items[0]": "foo",                      // Passes, string comparison
    "items[1]": "<re>/(foo|bar|baz)/</re>", // Passes as the expression matches "bar"
    "items[2]": "bar",                      // Fails as the value is baz
    "items[3]":
    {
        "some":
        {
            "foo": "<re>/ba(r|z)/</re>"     // Passes as the expression matches "bar"
        }
    },
    "items[4]": "bar"                       // Throws an OutOfRangeException exception as the index does not exist
}
```

If you use the index checking against something that is not a numeric array, the extension will throw an `InvalidArgumentException` exception.

You can also assert that values exists in numerically indexed arrays. Consider the following JSON response body:

```json
{
    "list": [1, 2, 3, "four", [1], {"foo": "bar"}]
}
```

To assert that one or more of the values exist, use the following:

```json
{
    "list": [3, [1], {"foo": "bar"}]
}
```

The index is not taken into consideration when comparing, it simply checks if the values specified are present in the list.

If the response body contains a numerical array as the root node, you will need to use a special syntax for validation. Consider the following response body:

```json
[
    "foo",
    123,
    {
        "foo": "bar"
    },
    "bar",
    [1, 2, 3]
]
```

To validate this, use the following syntax:

```json
{
    "[0]": "foo",
    "[1]": 123,
    "[2]": {
        "foo": "bar"
    },
    "[3]": "<re>/bar/</re>",
    "[4]": "@length(3)"
}
```

This simply refers to the indexes in the root numerical array.

## Extending the extension

If you want to implement your own assertions, or for instance add custom authentication for all requests made against your APIs you can extend the context class provided by the extension to access the client, request, request options and response properties.

**Example:**

```php
<?php
use Imbo\BehatApiExtension\Context\ApiContext,
    Behat\Behat\Hook\Scope\BeforeFeatureScope,
    Assert\Assertion;

class FeatureContext extends ApiContext {
    /**
     * @BeforeFeature
     */
    public function setApiAuth(BeforeFeatureScope $scope) {
        // For instance add a middleware to the client to handle API authentication

        // ...
    }

    /**
     * Custom assertion, match the HTTP reason phrase
     *
     * @param string $phrase Expected HTTP reason phrase
     * @Then the response reason phrase is :phrase
     */
    public function assertResponseReasonPhrase($phrase) {
        Assertion::same($phrase, $actual = $this->response->getReasonPhrase(), sprintf(
            'Invalid HTTP reason phrase, expected "%s", got "%s"',
            $phrase,
            $actual
        ));
    }
}
```

The client, request, request options and response are accessed via the protected `$this->client`, `$this->request`, `$this->requestOptions` and `$this->response` properties respectively. Keep in mind that `$this->response` is not populated until the client has made a request, i.e. after any of the aforementioned `@When` steps have finished. Since Guzzle implements [PSR-7](http://www.php-fig.org/psr/psr-7/), both `$this->request` and `$this->response` are value objects, which means that they can not be modified, but needs to be re-set for new values to stick:

```php
$this->request = $this->request->withAddedHeader('Some-Custom-Header', 'some value');
```

If you end up adding some generic assertions, please don't hesitate to send a pull request if you think they should be added to this project.

## Copyright / License

Copyright (c) 2016, Christer Edvartsen <cogo@starzinger.net>

Licensed under the MIT License
