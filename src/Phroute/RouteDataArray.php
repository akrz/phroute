<?php

namespace Phroute\Phroute;

class RouteDataArray implements RouteDataInterface, \JsonSerializable
{
    /**
     * @var array
     */
    private $variableRoutes;

    /**
     * @var array
     */
    private $staticRoutes;

    /**
     * @var array
     */
    private $filters;

    /**
     * @param array $staticRoutes
     * @param array $variableRoutes
     * @param array $filters
     */
    public function __construct(array $staticRoutes, array $variableRoutes, array $filters)
    {
        $this->staticRoutes = $staticRoutes;

        $this->variableRoutes = $variableRoutes;

        $this->filters = $filters;
    }

    /**
     * @return array
     */
    public function getStaticRoutes()
    {
        return $this->staticRoutes;
    }

    /**
     * @return array
     */
    public function getVariableRoutes()
    {
        return $this->variableRoutes;
    }

    /**
     * @return mixed
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        return [
            'variableRoutes' => $this->variableRoutes,
            'staticRoutes' => $this->staticRoutes,
            'filters' => $this->filters,
        ];
    }
}
