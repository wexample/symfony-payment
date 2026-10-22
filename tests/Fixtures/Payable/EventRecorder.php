<?php

namespace Wexample\SymfonyPayment\Tests\Fixtures\Payable;

class EventRecorder
{
    /** @var list<object> */
    public array $events = [];

    public function record(object $event): void
    {
        $this->events[] = $event;
    }

    public function count(string $class): int
    {
        return count(array_filter($this->events, fn (object $event) => $event instanceof $class));
    }
}
