<?php

namespace AceOugi;

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
     * Alias of $this->push()
     * @param callable $callable
     */
    public function add($callable)
    {
        $this->queue->push($callable);
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
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @return mixed
     */
    public function dispatch(\Psr\Http\Message\ServerRequestInterface $request, \Psr\Http\Message\ResponseInterface $response)
    {
        if ($this->queue->isEmpty())
            return $response;

        return call_user_func($this->resolve($this->queue->dequeue()), $request, $response, $this);
    }

    /**
     * Alias of $this->dispatch
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @return mixed
     */
    public function run(\Psr\Http\Message\ServerRequestInterface $request, \Psr\Http\Message\ResponseInterface $response)
    {
        return $this->dispatch($request, $response);
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param callable[] ...$callables unshift callables in queue
     * @return mixed
     */
    public function __invoke(\Psr\Http\Message\ServerRequestInterface $request, \Psr\Http\Message\ResponseInterface $response, ...$callables)
    {
        while ($callable = array_pop($callables))
            $this->queue->unshift($callable);

        return $this->dispatch($request, $response);
    }
}
