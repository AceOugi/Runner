<?php

namespace AceOugi;

class Pipeline
{
    /** @var callable */
    protected $resolver;

    /** @var \SplQueue */
    protected $queue;

    /**
     * Runner constructor.
     * @param callable|null $resolver
     */
    public function __construct(callable $resolver = null)
    {
        $this->resolver = $resolver;
        $this->queue = new \SplQueue();
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
        foreach ($callables as $callable)
            $this->queue->unshift($callable);

        return $this;
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @return mixed
     */
    public function call(\Psr\Http\Message\ServerRequestInterface $request, \Psr\Http\Message\ResponseInterface $response)
    {
        return $this->__invoke($request, $response);
    }

    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param \Psr\Http\Message\ResponseInterface $response
     * @param callable[] ...$callables
     * @return mixed
     */
    public function __invoke(\Psr\Http\Message\ServerRequestInterface $request, \Psr\Http\Message\ResponseInterface $response, ...$callables)
    {
        $this->prepend(...$callables);

        return $this->queue->isEmpty() ? $response : call_user_func($this->resolve($this->queue->dequeue()), $request, $response, $this);
    }
}
