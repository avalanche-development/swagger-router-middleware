swagger-router-middleware
==============

PHP middleware that parses and attaches [swagger](http://swagger.io/) information to a request object.

[![Build Status](https://travis-ci.org/avalanche-development/swagger-router-middleware.svg?branch=master)](https://travis-ci.org/avalanche-development/swagger-router-middleware)
[![Code Climate](https://codeclimate.com/github/avalanche-development/swagger-router-middleware/badges/gpa.svg)](https://codeclimate.com/github/avalanche-development/swagger-router-middleware)
[![Test Coverage](https://codeclimate.com/github/avalanche-development/swagger-router-middleware/badges/coverage.svg)](https://codeclimate.com/github/avalanche-development/swagger-router-middleware/coverage)

## Installation

It's recommended that you use [Composer](https://getcomposer.org/) to install swagger-router-middleware.

```bash
$ composer require avalanche-development/swagger-router-middleware
```

swagger-router-middleware requires PHP 5.6 or newer.

## Usage

This middleware is instantiated with swagger (in the form of an array) and then, when invoked as middleware, will walk through the swagger document and parse things out. Specifically, it will pull out the path and operation, resolve parameters and security definitions, and parse out parameters based on the swagger definition.

```php
$router = new AvalancheDevelopment\SwaggerRouterMiddleware\Router([..swagger..]);
$result = $router($request, $response, $next); // middleware signature
```

It is recommended that this is one of the top items in the stack, as the swagger information that is parsed out can be used for request/response validation deeper in.

### Interface

Once everything passes through successfully, the $request object will have the following attribute passed on.

```php
'swagger' => [
    'apiPath' => '/comments/{comment_id}', // matched string path
    'path' => [ ... ], // full path definition
    'operation' => [ ... ], // specific operation definition
    'params' => [ ... ], // resolved list of parameters for this operation
    'security' => [ ... ], // resolved list of securities for this operation
]
```

An important note is that each parameter in the list will include a 'value' key that, if the parameter was passed into the request (or has a default), will be populated.

### Documentation Route

If the standard 'documentation route' is detected (path of /api-docs), the rest of the stack is immediately skipped and the swagger spec is returned as json. An error with json_encode will throw a standard \Exception.

### Invalid Requests

There is some routing being done here. If the request route cannot be found in swagger, or if the method is not supported, appropriate [peel](https://github.com/avalanche-development/peel) exceptions are thrown. Also, if there is an error with parameter parsing that appears to be an issue with request, a peel BadRequest is thrown. An error handler can listen for these HttpErrorInterface exceptions and respond appropriately.

### Parameter Parsing

The middleware will do it's best to parse out the parameters for the request without too much validation. It loops through the swagger definition, looking for the parameters that are applicable for the route, and pull them from header/query/path/etc. Parameters will be cast to their defined type (most notable, date and date-time will be cast into DateTime objects). If there is an issue on this step (like, an object cannot be parsed or a date is invalid) this is where that BadRequest will be thrown.

Again, this middleware will not check the existence or validity of parameters based on the spec. It only tries to pull and format it and expects something else in the stack to verify integrity.

## Development

This library is in active development. Some things are not yet supported (such as form parsing). These features will throw an \Exception if they are hit.

### Tests

To execute the test suite, you'll need phpunit (and to install package with dev dependencies).

```bash
$ phpunit
```

## License

swagger-router-middleware is licensed under the MIT license. See [License File](LICENSE.md) for more information.
