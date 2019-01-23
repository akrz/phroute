<?php namespace Phroute\Phroute;

use Phroute\Phroute\Exception\HttpMethodNotAllowedException;
use Phroute\Phroute\Exception\HttpRouteNotFoundException;

class Dispatcher {

    private $staticRouteMap;
    private $variableRouteData;
    private $filters;
    private $handlerResolver;

    /**
     * Create a new route dispatcher.
     *
     * @param RouteDataInterface $data
     * @param HandlerResolverInterface $resolver
     */
    public function __construct(RouteDataInterface $data, HandlerResolverInterface $resolver = null)
    {
        $this->staticRouteMap = $data->getStaticRoutes();

        $this->variableRouteData = $data->getVariableRoutes();

        $this->filters = $data->getFilters();

        if ($resolver === null)
        {
            $this->handlerResolver = new HandlerResolver();
        }
        else
        {
            $this->handlerResolver = $resolver;
        }
    }

    /**
     * @param $httpMethod
     * @param $uri
     *
     * @return \Phroute\Phroute\Route
     */
    public function matchRoute($httpMethod, $uri)
    {
        list($handler, $filters, $vars, $name, $defaults) = $this->dispatchRoute($httpMethod, trim($uri, '/'));

        $route = new Route();
        $route
            ->setHandler($handler)
            ->setFilters($filters)
            ->setVars($vars)
            ->setName($name)
            ->setDefaults($defaults);

        return $route;
    }

    /**
     * Dispatch a route for the given HTTP Method / URI.
     *
     * @param Route $route
     *
     * @return mixed|null
     */
    public function dispatch(Route $route)
    {
        $handler = $route->getHandler();
        $filters = $route->getFilters();
        $vars = $route->getVars();

        list($beforeFilter, $afterFilter) = $this->parseFilters($filters);

        if(($response = $this->dispatchFilters($beforeFilter)) !== null)
        {
            return $response;
        }

        $resolvedHandler = $this->handlerResolver->resolve($handler);
        if (is_array($resolvedHandler)) {
            $resolvedHandler = $resolvedHandler[0];
            $vars = array_values($vars);
            $response = $resolvedHandler(...$vars);
        } else {
            $response = call_user_func_array($resolvedHandler, $vars);
        }

        return $this->dispatchFilters($afterFilter, $response);
    }

    /**
     * Dispatch a route filter.
     *
     * @param $filters
     * @param null $response
     * @return mixed|null
     */
    private function dispatchFilters($filters, $response = null)
    {
        while($filter = array_shift($filters))
        {
            $handler = $this->handlerResolver->resolve($filter);

            if (is_callable($handler[0])) {
                $handler = $handler[0];
                $filteredResponse = $handler($response);
            } else {
                $filteredResponse = call_user_func($handler, $response);
            }

            if($filteredResponse !== null)
            {
                return $filteredResponse;
            }
        }

        return $response;
    }

    /**
     * Normalise the array filters attached to the route and merge with any global filters.
     *
     * @param $filters
     * @return array
     */
    private function parseFilters($filters)
    {
        $beforeFilter = array();
        $afterFilter = array();

        if(isset($filters[Route::BEFORE]))
        {
            $beforeFilter = array_intersect_key($this->filters, array_flip((array) $filters[Route::BEFORE]));
        }

        if(isset($filters[Route::AFTER]))
        {
            $afterFilter = array_intersect_key($this->filters, array_flip((array) $filters[Route::AFTER]));
        }

        return array($beforeFilter, $afterFilter);
    }

    /**
     * Perform the route dispatching. Check static routes first followed by variable routes.
     *
     * @param $httpMethod
     * @param $uri
     * @throws Exception\HttpRouteNotFoundException
     */
    private function dispatchRoute($httpMethod, $uri)
    {
        if (isset($this->staticRouteMap[$uri]))
        {
            return $this->dispatchStaticRoute($httpMethod, $uri);
        }

        return $this->dispatchVariableRoute($httpMethod, $uri);
    }

    /**
     * Handle the dispatching of static routes.
     *
     * @param $httpMethod
     * @param $uri
     * @return mixed
     * @throws Exception\HttpMethodNotAllowedException
     */
    private function dispatchStaticRoute($httpMethod, $uri)
    {
        $routes = $this->staticRouteMap[$uri];

        if (!isset($routes[$httpMethod]))
        {
            $httpMethod = $this->checkFallbacks($routes, $httpMethod);
        }

        return $routes[$httpMethod];
    }

    /**
     * Check fallback routes: HEAD for GET requests followed by the ANY attachment.
     *
     * @param $routes
     * @param $httpMethod
     * @throws Exception\HttpMethodNotAllowedException
     */
    private function checkFallbacks($routes, $httpMethod)
    {
        if ($httpMethod === Route::OPTIONS) {
            throw new HttpMethodNotAllowedException('Allow: ' . implode(', ', array_keys($routes)));
        }
        
        $additional = array(Route::ANY);

        if($httpMethod === Route::HEAD)
        {
            $additional[] = Route::GET;
        }

        foreach($additional as $method)
        {
            if(isset($routes[$method]))
            {
                return $method;
            }
        }

        throw new HttpMethodNotAllowedException('Allow: ' . implode(', ', array_keys($routes)));
    }

    /**
     * Handle the dispatching of variable routes.
     *
     * @param $httpMethod
     * @param $uri
     * @throws Exception\HttpMethodNotAllowedException
     * @throws Exception\HttpRouteNotFoundException
     */
    private function dispatchVariableRoute($httpMethod, $uri)
    {
        foreach ($this->variableRouteData as $data)
        {
            if (!preg_match($data['regex'], $uri, $matches))
            {
                continue;
            }

            $count = count($matches);

            while(!isset($data['routeMap'][$count++]));

            $routes = $data['routeMap'][$count - 1];

            if (!isset($routes[$httpMethod]))
            {
                $httpMethod = $this->checkFallbacks($routes, $httpMethod);
            }

            foreach (array_values($routes[$httpMethod][2]) as $i => $varName)
            {
                if(!isset($matches[$i + 1]) || $matches[$i + 1] === '')
                {
                    unset($routes[$httpMethod][2][$varName]);
                }
                else
                {
                    $routes[$httpMethod][2][$varName] = $matches[$i + 1];
                }
            }

            return $routes[$httpMethod];
        }

        throw new HttpRouteNotFoundException('Route ' . $uri . ' does not exist');
    }
}
