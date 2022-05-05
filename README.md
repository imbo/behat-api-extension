# Behat API Extension

[![Current build Status](https://secure.travis-ci.org/imbo/behat-api-extension.png)](http://travis-ci.org/imbo/behat-api-extension)

This Behat extension provides an easy way to test JSON-based API's in [Behat 3](http://behat.org). Inspired by [behat/web-api-extension](https://github.com/Behat/WebApiExtension/) and originally written to test the [Imbo API](http://imbo.io).

## Installation / Configuration / Documentation

End-user docs can be found [here](https://behat-api-extension.readthedocs.io/).

## Copyright / License

Copyright (c) 2016-2020, Christer Edvartsen <cogo@starzinger.net>

Licensed under the MIT License

## Contribuiting

To run the tests first start the dev server with (you should have 8080 por available):

`composer run dev --timeout=0`

And finally run them with:

`composer test`