<?php

namespace AceOugi;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class Runner
{
    /** @var \SplQueue */
    protected $queue;

    /** @var callable */
    protected $resolver;

    /**
     * Runner constructor.
     * @param callable|null $resolver
     */
    public function __construct(callable $resolver = null)
    {
        $this->queue = new \SplQueue();
        $this->resolver = $resolver;
    }

    /**
     * @param callable $callable
     */
    public function push($callable)
    {
        $this->queue->push($callable);
    }

    /**
     * @return callable
     */
    public function pop()
    {
        return $this->queue->pop();
    }

    /**
     * Alias of $this->push()
     * @param callable $callable
     */
    public function add($callable)
    {
        $this->push($callable);
    }

    /**
     * @return callable|null
     */
    public function getResolver()
    {
        return $this->resolver;
    }

    /**
     * @param callable|null $resolver
     * @return $this
     */
    public function setResolver(callable $resolver = null)
    {
        $this->resolver = $resolver;
        return $this;
    }

    /**
     * @param callable $callable
     * @return callable
     */
    protected function resolve($callable) : callable
    {
        return ($resolver = $this->resolver) ? $resolver($callable) : $callable;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function dispatch(Request $request, Response $response)
    {
        if ($this->queue->isEmpty())
            return $response;

        return call_user_func($this->resolve($this->queue->dequeue()), $request, $response, $this);
    }

    /**
     * Alias of $this->dispatch
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public function run(Request $request, Response $response)
    {
        return $this->dispatch($request, $response);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @param callable[] ...$callables unshift callables in queue
     * @return mixed
     */
    public function __invoke(Request $request, Response $response, ...$callables)
    {
        while ($callable = array_pop($callables))
            $this->queue->unshift($callable);

        return $this->dispatch($request, $response);
    }
}
