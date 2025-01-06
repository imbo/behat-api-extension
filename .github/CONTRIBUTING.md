# Contributing to Behat API Extension

If you want to contribute to the Behat API Extension please follow the following guidelines.

## Running tests and static analysis

Behat API Extension has both [Behat](http://docs.behat.org/) and [PHPUnit](https://phpunit.de/) tests, and when adding new features or fixing bugs you are required to add relevant test cases.

The Behat tests requires a web server hosting the `features/bootstrap/index.php` script. A quick and easy alternative is to use PHPs built in web server:

    php -S localhost:8080 -t ./features/bootstrap > server.log 2>&1

After this has been started you can execute the test suites by running:

    ./vendor/bin/behat --strict
    ./vendor/bin/phpunit

## Documentation

The extension uses [Sphinx](http://www.sphinx-doc.org/en/stable/) for documentation, and all end-user documentation resides in the `docs` directory. To generate the current documentation after checking out your fork simply run the `docs` composer script:

    composer run docs

from the project root directory. If the command fails you are most likely missing packages not installable by Composer. Install missing packages and re-run the command to generate docs.

## Reporting bugs

Use the [issue tracker on GitHub](https://github.com/imbo/behat-api-extension/issues) for this. Please add necessary steps that can reproduce the bugs.

## Submitting a pull request

If you want to implement a new feature, fork this project and create a feature branch called `feature/my-awesome-feature`, and send a pull request. The feature needs to be fully documented and tested before it will be merged.

If the pull request is a bug fix, remember to file an issue in the issue tracker first, then create a branch called `issue/<issue number>`. One or more test cases to verify the bug is required. When creating specific test cases for issues, please add a `@see` tag to the docblock, or as a comment in the feature file. For instance:

```php
/**
 * @see https://github.com/imbo/behat-api-extension/issues/<issue number>
 */
public function testSomething()
{
    // ...
}
```

Please also specify which commit that resolves the bug by adding `Resolves #<issue number>` to the commit message.

## Coding standards

This library follows the [imbo/imbo-coding-standard](https://github.com/imbo/imbo-coding-standard) coding standard, and runs [php-cs-fixer](https://github.com/FriendsOfPHP/PHP-CS-Fixer) as a step in the CI workflow.
