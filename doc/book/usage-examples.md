# Usage

## Converting a PSR-7 ServerRequestInterface to a Zend\Http\PhpEnvironment\Request

The PSR-7 [ServerRequestInterface](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-7-http-message.md#321-psrhttpmessageserverrequestinterface) 
corresponds to the zend-http [PhpEnvironment\Request](https://github.com/zendframework/zend-http/blob/master/src/PhpEnvironment/Request.php).

To convert from a PSR-7 instance to a zend-http instance, use
`Zend\Psr7Bridge\PsrServerRequest::toZend()`. This method takes up to two
arguments:

- the `ServerRequestInterface` instance to convert.
- a boolean flag indicating whether or not to do a "shallow" conversion.

*Shallow conversions* omit:

- body parameters ("post" in zend-http)
- uploaded files
- the body content

It is useful to omit these for purposes of routing, for instance, when you may
not need this more process-intensive data. By default, the `$shallow` flag is
`false`, meaning a full conversion is done.

### Examples

- Doing a full conversion:

  ```php
  <?php
  use Zend\Http\PhpEnvironment\Response;
  use Zend\Psr7Bridge\Psr7ServerRequest;

  // Assume $controller is a Zend\Mvc\Controller\AbstractController instance.
  $result = $controller->dispatch(
      PsrServerRequest::toZend($request),
      new Response()
  );
  ```

- Doing a shallow conversion:

  ```php
  <?php
  use Zend\Psr7Bridge\Psr7ServerRequest;

  // Assume $router is a Zend\Mvc\Router\Http\TreeRouteStack instance.
  $match = $router->match(Psr7ServerRequest::toZend($request, true));
  ```
