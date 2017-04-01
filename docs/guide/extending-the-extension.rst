Extending the extension
=======================

If you want to implement your own assertions, or for instance add custom authentication for all requests made against your APIs you can extend the context class provided by the extension to access the client, request, request options, response and the array contains comparator properties. These properties are accessed via the protected ``$this->client``, ``$this->request``, ``$this->requestOptions``, ``$this->response`` and ``$this->arrayContainsComparator`` properties respectively. Keep in mind that ``$this->response`` is not populated until the client has made a request, i.e. after any of the aforementioned ``@When`` steps have finished.

Add ``@Given``'s, ``@When``'s and/or ``@Then``'s
------------------------------------------------

If you want to add a ``@Given``, ``@When`` and/or ``@Then`` step, simply add a method in your ``FeatureContext`` class along with the step using annotations in the ``phpdoc`` block:

.. code-block:: php

    <?php
    use Imbo\BehatApiExtension\Context\ApiContext;
    use Imbo\BehatApiExtension\Exception\AssertionFailedException as Failure;

    class FeatureContext extends ApiContext {
        /**
         * @Then I want to check something
         */
        public function assertSomething() {
            // do some assertions on $this->response, and throw a Failure exception is the
            // assertion fails.
        }
    }

With the above example you can now use ``Then I want to check something`` can be used in your feature files along with the steps defined by the extension.

Manipulate the API client
-------------------------

If you wish to manipulate the API client (``GuzzleHttp\Client``) this can be done in the initialization-phase:

.. code-block:: php

    <?php
    use Imbo\BehatApiExtension\Context\ApiContext;
    use GuzzleHttp\ClientInterface;
    use GuzzleHttp\Middleware;
    use Psr\Http\Message\RequestInterface;

    class FeatureContext extends ApiContext {
        /**
         * Manipulate the API client
         *
         * @param ClientInterface $client
         * @return self
         */
        public function setClient(ClientInterface $client) {
            $stack = $client->getConfig('handler');
            $stack->push(Middleware::mapRequest(function(RequestInterface $request) {
                // Add something to the request and return the new instance
                return $request->withAddedHeader('Some-Custom-Header', 'some value');
            }));

            return parent::setClient($client);
        }
    }

Register custom matcher functions
---------------------------------

The extension comes with some built in matcher functions used to verify JSON-content (see :ref:`then-the-response-body-contains-json`), like for instance ``@arrayLength`` and ``@regExp``. These functions are basically callbacks to PHP methods / functions, so you can easily define your own and use them in your tests:

.. code-block:: php

    <?php
    use Imbo\BehatApiExtension\Context\ApiContext;
    use Imbo\BehatApiExtension\ArrayContainsComparator;

    class FeatureContext extends ApiContext {
        /**
         * Add a custom function called @gt to the comparator
         *
         * @param ArrayContainsComparator $comparator
         * @return self
         */
        public function setArrayContainsComparator(ArrayContainsComparator $comparator) {
            $comparator->addFunction('gt', function($num, $gt) {
                $num = (int) $num;
                $gt = (int) $gt;

                if ($num <= $gt) {
                    throw new InvalidArgumentException(sprintf(
                        'Expected number to be greater than %d, got: %d.',
                        $gt,
                        $num
                    ));
                }
            });

            return parent::setArrayContainsComparator($comparator);
        }
    }

The above snippet adds a custom matcher function called ``@gt`` that can be used to check if a number is greater than another number. Given the following response body:

.. code-block:: json

    {
      "number": 42
    }

the number in the ``number`` key could be verified with:

.. code-block:: gherkin

    Then the response body contains JSON:
        """
        {
          "number": "@gt(40)"
        }
        """
