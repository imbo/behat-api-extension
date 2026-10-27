Extending the extension
=======================

If you want to implement your own assertions, or for instance add custom authentication for all requests made against your APIs you can extend the context class provided by the extension to access the client, request, request options, response and the array contains comparator properties. These properties are accessed via the protected ``$this->client``, ``$this->request``, ``$this->requestOptions``, ``$this->response`` and ``$this->arrayContainsComparator`` properties respectively. Keep in mind that ``$this->response`` is not populated until the client has made a request, i.e. after any of the aforementioned ``When`` steps have finished.

Add ``Given``'s, ``When``'s and/or ``Then``'s
------------------------------------------------

If you want to add a ``Given``, ``When`` and/or ``Then`` step, simply add a method in your ``FeatureContext`` class along with the step using PHP attributes for the method:

.. code-block:: php

    <?php
    use Behat\Step\Then;
    use Imbo\BehatApiExtension\Context\ApiContext;
    use Imbo\BehatApiExtension\Exception\AssertionFailedException;

    class FeatureContext extends ApiContext
    {
        #[Then('I want to check something')]
        public function assertSomething()
        {
            // do some assertions on $this->response, and throw an AssertionFailedException
            // if the assertion fails.
        }
    }

With the above example you can now use ``Then I want to check something`` in your feature files along with the steps defined by the extension.

.. _configure-the-api-client:

Configure the API client
------------------------

If you wish to configure the internal API client (``GuzzleHttp\Client``) this can be done in the initialization-phase:

.. code-block:: php

    <?php
    use GuzzleHttp\HandlerStack;
    use GuzzleHttp\Middleware;
    use Imbo\BehatApiExtension\Context\ApiContext;

    class FeatureContext extends ApiContext
    {
        public function initializeClient(array $config): static
        {
            $stack = $config['handler'] ?? HandlerStack::create();
            $stack->push(Middleware::mapRequest(
                fn ($req) => $req->withAddedHeader('Some-Custom-Header', 'some value')
            ));
            $config['handler'] = $stack;
            return parent::initializeClient($config);
        }
    }

Register custom matcher functions
---------------------------------

The extension comes with some built in matcher functions used to verify JSON-content (see :ref:`then-the-response-body-contains-json`), like for instance ``@arrayLength`` and ``@regExp``. These functions are basically callbacks to PHP methods / functions, so you can easily define your own and use them in your tests:

.. code-block:: php

    <?php
    use Imbo\BehatApiExtension\Context\ApiContext;
    use Imbo\BehatApiExtension\ArrayContainsComparator;

    class FeatureContext extends ApiContext
    {
        /**
         * Add a custom function called @startsWith to the comparator
         */
        public function setArrayContainsComparator(ArrayContainsComparator $comparator): static
        {
            $comparator->addFunction('startsWith', static function (mixed $value, string $prefix): void {
                if (!is_string($value) || !str_starts_with($value, $prefix)) {
                    throw new InvalidArgumentException(sprintf(
                        'Expected a string starting with "%s".',
                        $prefix
                    ));
                }
            });

            return parent::setArrayContainsComparator($comparator);
        }
    }

The above snippet adds a custom matcher function called ``@startsWith`` that checks whether a string starts with the given prefix. A matcher must throw an exception if the value does not match; returning ``false`` does not indicate a mismatch. Given the following response body:

.. code-block:: json

    {
      "id": "API-42"
    }

the prefix of the string in the ``id`` key could be verified with:

.. code-block:: gherkin

    Then the response body contains JSON:
        """
        {
          "id": "@startsWith(API-)"
        }
        """
