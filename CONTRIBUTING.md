# Contributing to Behat API Extension

If you want to contribute to the Behat API Extension please follow the following guidelines.

## Running tests

Behat API Extension has both [Behat](http://docs.behat.org/) and [PHPUnit](https://phpunit.de/) tests, and when adding new features or fixing bugs you are required to add relevant test cases.

The Behat tests requires a web server hosting the `features/bootstrap/index.php` script. A quick and easy alternative is to use PHP's built in web server:

    composer start-server

which is a composer script that simply runs:

    php -S localhost:8080 -t ./features/bootstrap > server.log 2>&1 &

After this has been started you can execute the test suites by running:

    composer test

If you want to run the suites separately they can be executed like this:

    ./vendor/bin/behat --strict
    ./vendor/bin/phpunit

## Reporting bugs

Use the [issue tracker on GitHub](https://github.com/imbo/behat-api-extension/issues) for this. Please add necessary steps that can reproduce the bugs.

## Submitting a pull request

If you want to implement a new feature, fork this project and create a feature branch called `feature/my-awesome-feature`, and send a pull request. The feature needs to be fully documented and tested before it will be merged.

If the pull request is a bug fix, remember to file an issue in the issue tracker first, then create a branch called `issue/<issue number>`. One or more test cases to verify the bug is required. When creating specific test cases for issues, please add a `@see` tag to the docblock, or as a comment in the feature file. For instance:

```php
/**
 * @see https://github.com/imbo/behat-api-extension/issues/<issue number>
 */
public function testSomething
    // ...
}
```

## Coding standards

Simply use the same coding standard already found in the PHP files in the project.
