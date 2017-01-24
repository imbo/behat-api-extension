# Behat tests

For these tests to pass an HTTP server must be set up to listen on `http://localhost:8080`, with a document root set to the `bootstrap` directory. A composer script exists in `composer.json` that uses PHPs built in web server for this purpose.

In the project root directory, run the following command:

    composer start-server

and to execute the tests run (also from the project root):

    composer test-behat

or

    ./vendor/bin/behat --strict
