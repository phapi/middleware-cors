<?php


namespace Phapi\Middleware\Cors;

use Phapi\Contract\Middleware\Middleware;
use Phapi\Exception\BadRequest;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Middleware class for handling CORS requests.
 *
 * See https://developer.mozilla.org/en-US/docs/Web/HTTP/Access_control_CORS
 * for more information about CORS and how it works
 *
 * @category Phapi
 * @package  Phapi\Middleware\Cors
 * @author   Peter Ahinko <peter@ahinko.se>
 * @license  MIT (http://opensource.org/licenses/MIT)
 * @link     https://github.com/phapi/middleware-cors
 */
class Cors implements Middleware
{

    /**
     * Options / settings / configuration
     *
     * @var array
     */
    protected $options;

    /**
     * @var ServerRequestInterface
     */
    protected $request;

    /**
     * @var ResponseInterface
     */
    protected $response;

    public function __construct(array $options = [])
    {
        $this->options = $this->prepareOptions($options);
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, callable $next = null)
    {
        // We need to easily get the request and response object through out the middleware
        $this->request = $request;
        $this->response = $response;

        // Check if origin header is set, if it isn't then this isn't a CORS compliant request
        if ($this->request->hasHeader('origin')) {

            // Check if it is an OPTIONS request
            if (strtoupper($this->request->getMethod()) === 'OPTIONS') {
                // Check if Origin header is set and origin is allowed
                if (!$this->checkOrigin()) {
                    // The provided origin is not allowed
                    throw new BadRequest('Origin not allowed according to CORS');
                }

                // All checks passed so lets set some headers
                $this->createPreflightHeaders();
            } else {
                // This is not an OPTIONS request

                // Check if it is a CORS request (check for Origin header) and if the
                // Origin header value matches an allowed origin.
                if (!$this->checkOrigin()) {
                    throw new BadRequest('Origin not allowed according to CORS');
                }

                // Check if the request method is allowed
                if (!$this->checkMethod()) {
                    throw new BadRequest('Method not allowed according to CORS');
                }

                // All checks passed so lets set some headers
                $this->createRequestHeaders();
            }
        }

        // Call next middleware and return the response
        return $next($this->request, $this->response, $next);
    }

    /**
     * Create all headers for a preflight request
     *
     * @throws BadRequest
     */
    protected function createPreflightHeaders()
    {
        $headers = [];

        // Set Allowed origin header (either * or Origin from request)
        $headers = $this->createOriginHeader($headers);

        // Set support credentials header
        $headers = $this->createCredentialsHeader($headers);

        // Set exposed headers header
        $headers = $this->createExposedHeadersHeader($headers);

        // Set allowed methods header
        $headers = $this->createAllowedMethodsHeader($headers);

        // Set allowed headers header
        $headers = $this->createAllowedHeadersHeader($headers);

        // Set max age header
        $headers = $this->createMaxAgeHeader($headers);

        // Add the headers to the response
        foreach ($headers as $name => $value) {
            $this->response = $this->response->withAddedHeader($name, $value);
        }
    }

    /**
     * Create all headers for a regular request
     */
    protected function createRequestHeaders()
    {
        $headers = [];

        // Set Allowed origin header (either * or Origin from request)
        $headers = $this->createOriginHeader($headers);

        // Set support credentials header
        $headers = $this->createCredentialsHeader($headers);

        // Set exposed headers header
        $headers = $this->createExposedHeadersHeader($headers);

        // Add the headers to the response
        foreach ($headers as $name => $value) {
            $this->response = $this->response->withAddedHeader($name, $value);
        }
    }

    /**
     * Set Allowed origin header (either * or Origin from request)
     *
     * @param $headers
     * @return array|string
     */
    protected function createOriginHeader($headers)
    {
        // Check if all origins are allowed
        if ($this->options['allowedOrigins'] === true) {
            // All origins are allowed
            $headers['Access-Control-Allow-Origin'] = '*';
        } else {
            // Set the value of the request header
            $headers['Access-Control-Allow-Origin'] = $this->request->getHeaderLine('origin');

            // Set the Vary header (needs to be done according to the specification)
            $headers = $this->createVaryHeader($headers);
        }

        return $headers;
    }

    /**
     * Check if a allowed credentials header should be created
     * and create it with proper value if so
     *
     * @param $headers
     * @return mixed
     */
    protected function createCredentialsHeader($headers)
    {
        if ($this->options['supportsCredentials'] === true) {
            $headers['Access-Control-Allow-Credentials'] = 'true';
        }

        return $headers;
    }

    /**
     * Check if the exposed headers header should be created
     * and add the proper value to the header if it should
     * be created
     *
     * @param $headers
     * @return mixed
     */
    protected function createExposedHeadersHeader($headers)
    {
        if (count($this->options['exposedHeaders']) > 0) {
            $headers['Access-Control-Expose-Headers'] = implode(', ', $this->options['exposedHeaders']);
        }
        return $headers;
    }

    /**
     * Create the allowed methods header
     *
     * @param $headers
     * @return mixed
     * @throws BadRequest
     */
    protected function createAllowedMethodsHeader($headers)
    {
        // Make sure that the client provided access control request method header
        if (!$this->request->hasHeader('Access-Control-Request-Method')) {
            throw new BadRequest('The Access-Control-Request-Method header is missing from the request');
        }

        $headers['Access-Control-Allow-Methods'] = ($this->options['allowedMethods'] === true)
            ? strtoupper($this->request->getHeaderLine('Access-Control-Request-Method'))
            : implode(', ', $this->options['allowedMethods']);
        return $headers;
    }

    /**
     * Create the allowed headers header if needed
     *
     * @param $headers
     * @return mixed
     */
    protected function createAllowedHeadersHeader($headers)
    {
        // Check if the client provided an access control request headers header.
        // If the header isn't set, then there is no need to include the header in the response
        if ($this->request->hasHeader('Access-Control-Request-Headers')) {
            $headers['Access-Control-Allow-Headers'] = ($this->options['allowedHeaders'] === true)
                ? strtoupper($this->request->getHeaderLine('Access-Control-Request-Headers'))
                : implode(', ', $this->options['allowedHeaders']);
        }

        return $headers;
    }

    /**
     * Create the max age header
     *
     * @param $headers
     * @return mixed
     */
    protected function createMaxAgeHeader($headers)
    {
        $headers['Access-Control-Max-Age'] = $this->options['maxAge'];

        return $headers;
    }

    /**
     * Create/update the vary header
     *
     * @param $headers
     * @return mixed
     */
    protected function createVaryHeader($headers)
    {
        // Add the vary header, since PSR-7 has the withAddedHeader we don't
        // need to check for already existing Vary header. Origin will be added.
        $headers['Vary'] = 'Origin';

        return $headers;
    }

    /**
     * Check if the provided origin is allowed to
     * access the api
     *
     * @return bool
     */
    protected function checkOrigin()
    {
        // Check if we allow all "*"
        if ($this->options['allowedOrigins'] === true) {
            return true;
        }

        // Make sure the origin header is set and that the value (domain) is
        // in the allowed origins list.
        if (
            $this->request->hasHeader('origin') &&
            in_array($this->request->getHeaderLine('origin'), $this->options['allowedOrigins'])
        ) {
            return true;
        }

        return false;
    }

    /**
     * Make sure that the current request method is allowed to
     * be done to the api.
     *
     * @return bool
     */
    protected function checkMethod()
    {

        // Check if we allow all "*"
        if ($this->options['allowedMethods'] === true) {
            return true;
        }

        // Check if the current request method is allowed according to the configuration
        if (in_array($this->request->getMethod(), $this->options['allowedMethods'])) {
            return true;
        }

        return false;
    }

    /**
     * Prepare options by adding defaults and merging them with the provided
     * options. After that, check if we allow all origins, headers and options
     * and do some normalizing at the same time like fixing lowercase and UPPERCASE.
     *
     * @param $options
     * @return array
     */
    protected function prepareOptions($options)
    {
        $defaults = [
            'allowedOrigins' => [],
            'allowedMethods' => [],
            'allowedHeaders' => [],
            'exposedHeaders' => [],
            'maxAge' => 0,
            'supportsCredentials' => false,
        ];
        $options = array_merge($defaults, $options);

        // Check if we allow all origins
        if (in_array('*', $options['allowedOrigins'])) {
            $options['allowedOrigins'] = true;
        }

        // Check if we allow all headers
        if (in_array('*', $options['allowedHeaders'])) {
            $options['allowedHeaders'] = true;
        } else {
            // Normalize all set allowed headers by making them lowercase
            $options['allowedHeaders'] = array_map('strtolower', $options['allowedHeaders']);
        }

        // Check if we allow all methods
        if (in_array('*', $options['allowedMethods'])) {
            $options['allowedMethods'] = true;
        } else {
            // Normalize all allowed methods by making them UPPERCASE
            $options['allowedMethods'] = array_map('strtoupper', $options['allowedMethods']);
        }

        return $options;
    }
}
