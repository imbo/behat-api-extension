Extending the extension
=======================

If you want to implement your own assertions, or for instance add custom authentication for all requests made against your APIs you can extend the context class provided by the extension to access the client, request, request options and response properties. These properties are accessed via the protected ``$this->client``, ``$this->request``, ``$this->requestOptions`` and ``$this->response`` properties respectively. Keep in mind that ``$this->response`` is not populated until the client has made a request, i.e. after any of the aforementioned ``@When`` steps have finished. Since Guzzle implements `PSR-7 <http://www.php-fig.org/psr/psr-7/>`_, both ``$this->request`` and ``$this->response`` are value objects, which means that they can not be modified, but needs to be re-set for new values to stick:

.. code-block:: php

    $this->request = $this->request->withAddedHeader('Some-Custom-Header', 'some value');

If you end up adding some generic assertions, please don't hesitate to send a pull request if you think they should be added to this project.
