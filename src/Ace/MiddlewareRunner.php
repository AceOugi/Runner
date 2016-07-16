<?php

namespace Ace;

class MiddlewareRunner
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
     * @param callable[] $callables
     * @return self
     */
    public function append(...$callables)
    {
        foreach ($callables as $callable)
            $this->queue->enqueue($callable);

        return $this;
    }

    /**
     * @param callable[] $callables
     * @return self
     */
    public function prepend(...$callables)
    {
        while ($callable = array_pop($callables))
            $this->queue->unshift($callable);

        return $this;
    }

    /**
     * @param callable $callable
     * @return callable
     */
    protected function resolve($callable) : callable
    {
        return (!is_callable($callable) AND $resolver = $this->resolver) ? $resolver($callable) : $callable;
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param callable[] ...$callables
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function __invoke(\Psr\Http\Message\ServerRequestInterface $request, \Psr\Http\Message\ResponseInterface $response, ...$callables)
    {
        $this->prepend(...$callables);

        return $this->queue->isEmpty() ? $response : call_user_func($this->resolve($this->queue->dequeue()), $request, $response, $this);
    }
}
