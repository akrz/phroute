<?php namespace Phroute\Phroute;

class Route {

    /**
     * Constants for before and after filters
     */
    const BEFORE = 'before';

    const AFTER = 'after';

    const PREFIX = 'prefix';

    /**
     * Constants for common HTTP methods
     */
    const ANY = 'ANY';

    const GET = 'GET';

    const HEAD = 'HEAD';

    const POST = 'POST';

    const PUT = 'PUT';

    const PATCH = 'PATCH';

    const DELETE = 'DELETE';

    const OPTIONS = 'OPTIONS';

    private $handler;
    private $filters;
    private $vars;
    private $name;

    /**
     * @return mixed
     */
    public function getHandler()
    {
        return $this->handler;
    }

    /**
     * @param mixed $handler
     *
     * @return Route
     */
    public function setHandler($handler)
    {
        $this->handler = $handler;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getFilters()
    {
        return $this->filters;
    }

    /**
     * @param mixed $filters
     *
     * @return Route
     */
    public function setFilters($filters)
    {
        $this->filters = $filters;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getVars()
    {
        return $this->vars;
    }

    /**
     * @param mixed $vars
     *
     * @return Route
     */
    public function setVars($vars)
    {
        $this->vars = $vars;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     *
     * @return Route
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }
}
