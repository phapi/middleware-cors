<?php

namespace Phapi\Tests\Middleware;

use Phapi\Middleware\Cors\Cors;
use \PHPUnit_Framework_TestCase as TestCase;

/**
 * @coversDefaultClass \Phapi\Middleware\Cors\Cors
 */
class CorsTest extends TestCase {

    public function testNoCorsCall()
    {
        $options = [
            'allowedOrigins' => ['*'],
            'allowedMethods' => ['*'],
            'allowedHeaders' => ['*'],
            'exposedHeaders' => [],
            'maxAge' => 3600,
            'supportsCredentials' => false,
        ];

        $request = \Mockery::mock('Psr\Http\Message\ServerRequestInterface');
        $request->shouldReceive('hasHeader')->with('origin')->andReturn(false);

        $response = \Mockery::mock('Psr\Http\Message\ResponseInterface');

        $next = function ($request, $response, $next) {
            return $response;
        };

        $middleware = new Cors($options);
        $middleware($request, $response, $next);
    }

    public function testCorsCall()
    {
        $options = [
            'allowedOrigins' => ['*'],
            'allowedMethods' => ['*'],
            'allowedHeaders' => ['*'],
            'exposedHeaders' => ['Request-ID'],
            'maxAge' => 3600,
            'supportsCredentials' => true,
        ];

        $request = \Mockery::mock('Psr\Http\Message\ServerRequestInterface');
        $request->shouldReceive('hasHeader')->with('origin')->andReturn('http://foo.bar');
        $request->shouldReceive('getMethod')->andReturn('GET');

        $response = \Mockery::mock('Psr\Http\Message\ResponseInterface');
        $response->shouldReceive('withAddedHeader')->with('Access-Control-Allow-Origin', '*')->andReturnSelf();
        $response->shouldReceive('withAddedHeader')->with('Access-Control-Allow-Credentials', 'true')->andReturnSelf();
        $response->shouldReceive('withAddedHeader')->with('Access-Control-Expose-Headers', 'Request-ID')->andReturnSelf();
        //$response->shouldReceive('withAddedHeader')->with('Access-Control-Allow-Methods')->andReturnSelf();
        //$response->shouldReceive('withAddedHeader')->with('Access-Control-Allow-Headers')->andReturnSelf();
        //$response->shouldReceive('withAddedHeader')->with('Access-Control-Max-Age')->andReturnSelf();
        //$response->shouldReceive('withAddedHeader')->with('Vary')->andReturnSelf();

        $next = function ($request, $response, $next) {
            return $response;
        };

        $middleware = new Cors($options);
        $middleware($request, $response, $next);
    }

    public function testCorsOriginCall()
    {
        $options = [
            'allowedOrigins' => ['http://foo.bar'],
            'allowedMethods' => ['*'],
            'allowedHeaders' => ['*'],
            'exposedHeaders' => ['Request-ID'],
            'maxAge' => 3600,
            'supportsCredentials' => true,
        ];

        $request = \Mockery::mock('Psr\Http\Message\ServerRequestInterface');
        $request->shouldReceive('hasHeader')->with('origin')->andReturn(true);
        $request->shouldReceive('getHeaderLine')->with('origin')->andReturn('http://foo.bar');
        $request->shouldReceive('hasHeader')->with('Vary')->andReturn(false);
        $request->shouldReceive('getMethod')->andReturn('GET');

        $response = \Mockery::mock('Psr\Http\Message\ResponseInterface');
        $response->shouldReceive('withAddedHeader')->with('Access-Control-Allow-Origin', 'http://foo.bar')->andReturnSelf();
        $response->shouldReceive('withAddedHeader')->with('Access-Control-Allow-Credentials', 'true')->andReturnSelf();
        $response->shouldReceive('withAddedHeader')->with('Access-Control-Expose-Headers', 'Request-ID')->andReturnSelf();
        //$response->shouldReceive('withAddedHeader')->with('Access-Control-Allow-Methods')->andReturnSelf();
        //$response->shouldReceive('withAddedHeader')->with('Access-Control-Allow-Headers')->andReturnSelf();
        //$response->shouldReceive('withAddedHeader')->with('Access-Control-Max-Age')->andReturnSelf();
        $response->shouldReceive('withAddedHeader')->with('Vary', 'Origin')->andReturnSelf();

        $next = function ($request, $response, $next) {
            return $response;
        };

        $middleware = new Cors($options);
        $middleware($request, $response, $next);
    }

    public function testCorsOriginFailCall()
    {
        $options = [
            'allowedOrigins' => ['http://foo.fail'],
            'allowedMethods' => ['*'],
            'allowedHeaders' => ['*'],
            'exposedHeaders' => ['Request-ID'],
            'maxAge' => 3600,
            'supportsCredentials' => true,
        ];

        $request = \Mockery::mock('Psr\Http\Message\ServerRequestInterface');
        $request->shouldReceive('hasHeader')->with('origin')->andReturn(true);
        $request->shouldReceive('getHeaderLine')->with('origin')->andReturn('http://foo.bar');
        //$request->shouldReceive('hasHeader')->with('Vary')->andReturn(false);
        $request->shouldReceive('getMethod')->andReturn('GET');

        $response = \Mockery::mock('Psr\Http\Message\ResponseInterface');
        //$response->shouldReceive('withAddedHeader')->with('Access-Control-Allow-Origin', 'http://foo.bar')->andReturnSelf();
        //$response->shouldReceive('withAddedHeader')->with('Access-Control-Allow-Credentials', 'true')->andReturnSelf();
        //$response->shouldReceive('withAddedHeader')->with('Access-Control-Expose-Headers', 'Request-ID')->andReturnSelf();
        //$response->shouldReceive('withAddedHeader')->with('Access-Control-Allow-Methods')->andReturnSelf();
        //$response->shouldReceive('withAddedHeader')->with('Access-Control-Allow-Headers')->andReturnSelf();
        //$response->shouldReceive('withAddedHeader')->with('Access-Control-Max-Age')->andReturnSelf();
        //$response->shouldReceive('withAddedHeader')->with('Vary', 'Origin')->andReturnSelf();

        $next = function ($request, $response, $next) {
            return $response;
        };

        $middleware = new Cors($options);
        $this->setExpectedException('\Phapi\Exception\BadRequest', 'Origin not allowed according to CORS');
        $middleware($request, $response, $next);
    }

    public function testCorsMethodFailCall()
    {
        $options = [
            'allowedOrigins' => ['http://foo.bar'],
            'allowedMethods' => ['GET', 'POST', 'OPTIONS'],
            'allowedHeaders' => ['*'],
            'exposedHeaders' => ['Request-ID'],
            'maxAge' => 3600,
            'supportsCredentials' => true,
        ];

        $request = \Mockery::mock('Psr\Http\Message\ServerRequestInterface');
        $request->shouldReceive('hasHeader')->with('origin')->andReturn(true);
        $request->shouldReceive('getHeaderLine')->with('origin')->andReturn('http://foo.bar');
        //$request->shouldReceive('hasHeader')->with('Vary')->andReturn(false);
        $request->shouldReceive('getMethod')->andReturn('PUT');

        $response = \Mockery::mock('Psr\Http\Message\ResponseInterface');
        //$response->shouldReceive('withAddedHeader')->with('Access-Control-Allow-Origin', 'http://foo.bar')->andReturnSelf();
        //$response->shouldReceive('withAddedHeader')->with('Access-Control-Allow-Credentials', 'true')->andReturnSelf();
        //$response->shouldReceive('withAddedHeader')->with('Access-Control-Expose-Headers', 'Request-ID')->andReturnSelf();
        //$response->shouldReceive('withAddedHeader')->with('Access-Control-Allow-Methods')->andReturnSelf();
        //$response->shouldReceive('withAddedHeader')->with('Access-Control-Allow-Headers')->andReturnSelf();
        //$response->shouldReceive('withAddedHeader')->with('Access-Control-Max-Age')->andReturnSelf();
        //$response->shouldReceive('withAddedHeader')->with('Vary', 'Origin')->andReturnSelf();

        $next = function ($request, $response, $next) {
            return $response;
        };

        $middleware = new Cors($options);
        $this->setExpectedException('\Phapi\Exception\BadRequest', 'Method not allowed according to CORS');
        $middleware($request, $response, $next);
    }

    public function testCorsMethodPassCall()
    {
        $options = [
            'allowedOrigins' => ['http://foo.bar'],
            'allowedMethods' => ['GET', 'POST', 'OPTIONS'],
            'allowedHeaders' => ['*'],
            'exposedHeaders' => ['Request-ID'],
            'maxAge' => 3600,
            'supportsCredentials' => true,
        ];

        $request = \Mockery::mock('Psr\Http\Message\ServerRequestInterface');
        $request->shouldReceive('hasHeader')->with('origin')->andReturn(true);
        $request->shouldReceive('getHeaderLine')->with('origin')->andReturn('http://foo.bar');
        $request->shouldReceive('hasHeader')->with('Vary')->andReturn(false);
        $request->shouldReceive('getMethod')->andReturn('POST');

        $response = \Mockery::mock('Psr\Http\Message\ResponseInterface');
        $response->shouldReceive('withAddedHeader')->with('Access-Control-Allow-Origin', 'http://foo.bar')->andReturnSelf();
        $response->shouldReceive('withAddedHeader')->with('Access-Control-Allow-Credentials', 'true')->andReturnSelf();
        $response->shouldReceive('withAddedHeader')->with('Access-Control-Expose-Headers', 'Request-ID')->andReturnSelf();
        //$response->shouldReceive('withAddedHeader')->with('Access-Control-Allow-Methods')->andReturnSelf();
        //$response->shouldReceive('withAddedHeader')->with('Access-Control-Allow-Headers')->andReturnSelf();
        //$response->shouldReceive('withAddedHeader')->with('Access-Control-Max-Age')->andReturnSelf();
        $response->shouldReceive('withAddedHeader')->with('Vary', 'Origin')->andReturnSelf();

        $next = function ($request, $response, $next) {
            return $response;
        };

        $middleware = new Cors($options);
        $middleware($request, $response, $next);
    }

    public function testCorsVaryCall()
    {
        $options = [
            'allowedOrigins' => ['http://foo.bar'],
            'allowedMethods' => ['GET', 'POST', 'OPTIONS'],
            'allowedHeaders' => ['*'],
            'exposedHeaders' => ['Request-ID'],
            'maxAge' => 3600,
            'supportsCredentials' => true,
        ];

        $request = \Mockery::mock('Psr\Http\Message\ServerRequestInterface');
        $request->shouldReceive('hasHeader')->with('origin')->andReturn(true);
        $request->shouldReceive('getHeaderLine')->with('origin')->andReturn('http://foo.bar');
        $request->shouldReceive('hasHeader')->with('Vary')->andReturn(true);
        $request->shouldReceive('getHeaderLine')->with('Vary')->andReturn('Accepted-Encoding');
        $request->shouldReceive('getMethod')->andReturn('POST');

        $response = \Mockery::mock('Psr\Http\Message\ResponseInterface');
        $response->shouldReceive('withAddedHeader')->with('Access-Control-Allow-Origin', 'http://foo.bar')->andReturnSelf();
        $response->shouldReceive('withAddedHeader')->with('Access-Control-Allow-Credentials', 'true')->andReturnSelf();
        $response->shouldReceive('withAddedHeader')->with('Access-Control-Expose-Headers', 'Request-ID')->andReturnSelf();
        //$response->shouldReceive('withAddedHeader')->with('Access-Control-Allow-Methods')->andReturnSelf();
        //$response->shouldReceive('withAddedHeader')->with('Access-Control-Allow-Headers')->andReturnSelf();
        //$response->shouldReceive('withAddedHeader')->with('Access-Control-Max-Age')->andReturnSelf();
        $response->shouldReceive('withAddedHeader')->with('Vary', 'Origin')->andReturnSelf();

        $next = function ($request, $response, $next) {
            return $response;
        };

        $middleware = new Cors($options);
        $middleware($request, $response, $next);
    }

    public function testPreflightCall()
    {
        $options = [
            'allowedOrigins' => ['*'],
            'allowedMethods' => ['*'],
            'allowedHeaders' => ['*'],
            'exposedHeaders' => [],
            'maxAge' => 3600,
            'supportsCredentials' => false,
        ];

        $request = \Mockery::mock('Psr\Http\Message\ServerRequestInterface');
        $request->shouldReceive('hasHeader')->with('origin')->andReturn(true);
        $request->shouldReceive('getHeaderLine')->with('origin')->andReturn('http://foo.bar');
        //$request->shouldReceive('hasHeader')->with('Vary')->andReturn(true);
        //$request->shouldReceive('getHeaderLine')->with('Vary')->andReturn('Accepted-Encoding');
        $request->shouldReceive('getMethod')->andReturn('OPTIONS');
        $request->shouldReceive('hasHeader')->with('Access-Control-Request-Method')->andReturn(true);
        $request->shouldReceive('getHeaderLine')->with('Access-Control-Request-Method')->andReturn('GET');
        $request->shouldReceive('hasHeader')->with('Access-Control-Request-Headers')->andReturn(true);
        $request->shouldReceive('getHeaderLine')->with('Access-Control-Request-Headers')->andReturn('X-RATE-LIMIT-IDENTIFIER');

        $response = \Mockery::mock('Psr\Http\Message\ResponseInterface');
        $response->shouldReceive('withAddedHeader')->with('Access-Control-Allow-Origin', '*')->andReturnSelf();
        //$response->shouldReceive('withAddedHeader')->with('Access-Control-Allow-Credentials', 'true')->andReturnSelf();
        //$response->shouldReceive('withAddedHeader')->with('Access-Control-Expose-Headers', 'Request-ID')->andReturnSelf();
        $response->shouldReceive('withAddedHeader')->with('Access-Control-Allow-Methods', 'GET')->andReturnSelf();
        $response->shouldReceive('withAddedHeader')->with('Access-Control-Allow-Headers', 'X-RATE-LIMIT-IDENTIFIER')->andReturnSelf();
        $response->shouldReceive('withAddedHeader')->with('Access-Control-Max-Age', '3600')->andReturnSelf();
        //$response->shouldReceive('withAddedHeader')->with('Vary', 'Origin')->andReturnSelf();

        $next = function ($request, $response, $next) {
            return $response;
        };

        $middleware = new Cors($options);
        $middleware($request, $response, $next);
    }

    public function testPreflightOriginFailCall()
    {
        $options = [
            'allowedOrigins' => ['http://foo.bar'],
            'allowedMethods' => ['*'],
            'allowedHeaders' => ['*'],
            'exposedHeaders' => [],
            'maxAge' => 3600,
            'supportsCredentials' => false,
        ];

        $request = \Mockery::mock('Psr\Http\Message\ServerRequestInterface');
        $request->shouldReceive('hasHeader')->with('origin')->andReturn(true);
        $request->shouldReceive('getHeaderLine')->with('origin')->andReturn('http://foo.fail');
        //$request->shouldReceive('hasHeader')->with('Vary')->andReturn(true);
        //$request->shouldReceive('getHeaderLine')->with('Vary')->andReturn('Accepted-Encoding');
        $request->shouldReceive('getMethod')->andReturn('OPTIONS');
        $request->shouldReceive('hasHeader')->with('Access-Control-Request-Method')->andReturn(true);
        $request->shouldReceive('getHeaderLine')->with('Access-Control-Request-Method')->andReturn('GET');
        //$request->shouldReceive('hasHeader')->with('Access-Control-Request-Headers')->andReturn(true);
        //$request->shouldReceive('getHeaderLine')->with('Access-Control-Request-Headers')->andReturn('X-RATE-LIMIT-IDENTIFIER');

        $response = \Mockery::mock('Psr\Http\Message\ResponseInterface');
        //$response->shouldReceive('withAddedHeader')->with('Access-Control-Allow-Origin', '*')->andReturnSelf();
        //$response->shouldReceive('withAddedHeader')->with('Access-Control-Allow-Credentials', 'true')->andReturnSelf();
        //$response->shouldReceive('withAddedHeader')->with('Access-Control-Expose-Headers', 'Request-ID')->andReturnSelf();
        //$response->shouldReceive('withAddedHeader')->with('Access-Control-Allow-Methods', 'GET')->andReturnSelf();
        //$response->shouldReceive('withAddedHeader')->with('Access-Control-Allow-Headers', 'X-RATE-LIMIT-IDENTIFIER')->andReturnSelf();
        //$response->shouldReceive('withAddedHeader')->with('Access-Control-Max-Age', '3600')->andReturnSelf();
        //$response->shouldReceive('withAddedHeader')->with('Vary', 'Origin')->andReturnSelf();

        $next = function ($request, $response, $next) {
            return $response;
        };

        $middleware = new Cors($options);

        $this->setExpectedException('\Phapi\Exception\BadRequest', 'Origin not allowed');
        $middleware($request, $response, $next);
    }

    public function testPreflightMissingHeaderCall()
    {
        $options = [
            'allowedOrigins' => ['http://foo.bar'],
            'allowedMethods' => ['*'],
            'allowedHeaders' => ['*'],
            'exposedHeaders' => [],
            'maxAge' => 3600,
            'supportsCredentials' => false,
        ];

        $request = \Mockery::mock('Psr\Http\Message\ServerRequestInterface');
        $request->shouldReceive('hasHeader')->with('origin')->andReturn(true);
        $request->shouldReceive('getHeaderLine')->with('origin')->andReturn('http://foo.bar');
        //$request->shouldReceive('hasHeader')->with('Vary')->andReturn(true);
        //$request->shouldReceive('getHeaderLine')->with('Vary')->andReturn('Accepted-Encoding');
        $request->shouldReceive('getMethod')->andReturn('OPTIONS');
        $request->shouldReceive('hasHeader')->with('Access-Control-Request-Method')->andReturn(false);
        //$request->shouldReceive('getHeaderLine')->with('Access-Control-Request-Method')->andReturn('GET');
        //$request->shouldReceive('hasHeader')->with('Access-Control-Request-Headers')->andReturn(true);
        //$request->shouldReceive('getHeaderLine')->with('Access-Control-Request-Headers')->andReturn('X-RATE-LIMIT-IDENTIFIER');

        $response = \Mockery::mock('Psr\Http\Message\ResponseInterface');
        //$response->shouldReceive('withAddedHeader')->with('Access-Control-Allow-Origin', '*')->andReturnSelf();
        //$response->shouldReceive('withAddedHeader')->with('Access-Control-Allow-Credentials', 'true')->andReturnSelf();
        //$response->shouldReceive('withAddedHeader')->with('Access-Control-Expose-Headers', 'Request-ID')->andReturnSelf();
        //$response->shouldReceive('withAddedHeader')->with('Access-Control-Allow-Methods', 'GET')->andReturnSelf();
        //$response->shouldReceive('withAddedHeader')->with('Access-Control-Allow-Headers', 'X-RATE-LIMIT-IDENTIFIER')->andReturnSelf();
        //$response->shouldReceive('withAddedHeader')->with('Access-Control-Max-Age', '3600')->andReturnSelf();
        //$response->shouldReceive('withAddedHeader')->with('Vary', 'Origin')->andReturnSelf();

        $next = function ($request, $response, $next) {
            return $response;
        };

        $middleware = new Cors($options);

        $this->setExpectedException('\Phapi\Exception\BadRequest', 'The Access-Control-Request-Method header is missing from the request');
        $middleware($request, $response, $next);
    }

    public function testPreflightRequestHeadersCall()
    {
        $options = [
            'allowedOrigins' => ['*'],
            'allowedMethods' => ['*'],
            'allowedHeaders' => ['X-Rate-Limit-Identifier'],
            'exposedHeaders' => [],
            'maxAge' => 3600,
            'supportsCredentials' => false,
        ];

        $request = \Mockery::mock('Psr\Http\Message\ServerRequestInterface');
        $request->shouldReceive('hasHeader')->with('origin')->andReturn(true);
        $request->shouldReceive('getHeaderLine')->with('origin')->andReturn('http://foo.bar');
        //$request->shouldReceive('hasHeader')->with('Vary')->andReturn(true);
        //$request->shouldReceive('getHeaderLine')->with('Vary')->andReturn('Accepted-Encoding');
        $request->shouldReceive('getMethod')->andReturn('OPTIONS');
        $request->shouldReceive('hasHeader')->with('Access-Control-Request-Method')->andReturn(true);
        $request->shouldReceive('getHeaderLine')->with('Access-Control-Request-Method')->andReturn('GET');
        $request->shouldReceive('hasHeader')->with('Access-Control-Request-Headers')->andReturn(true);
        $request->shouldReceive('getHeaderLine')->with('Access-Control-Request-Headers')->andReturn('X-RATE-LIMIT-IDENTIFIER');

        $response = \Mockery::mock('Psr\Http\Message\ResponseInterface');
        $response->shouldReceive('withAddedHeader')->with('Access-Control-Allow-Origin', '*')->andReturnSelf();
        //$response->shouldReceive('withAddedHeader')->with('Access-Control-Allow-Credentials', 'true')->andReturnSelf();
        //$response->shouldReceive('withAddedHeader')->with('Access-Control-Expose-Headers', 'Request-ID')->andReturnSelf();
        $response->shouldReceive('withAddedHeader')->with('Access-Control-Allow-Methods', 'GET')->andReturnSelf();
        $response->shouldReceive('withAddedHeader')->with('Access-Control-Allow-Headers', 'x-rate-limit-identifier')->andReturnSelf();
        $response->shouldReceive('withAddedHeader')->with('Access-Control-Max-Age', '3600')->andReturnSelf();
        //$response->shouldReceive('withAddedHeader')->with('Vary', 'Origin')->andReturnSelf();

        $next = function ($request, $response, $next) {
            return $response;
        };

        $middleware = new Cors($options);
        $middleware($request, $response, $next);
    }
}
