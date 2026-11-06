<?php

namespace Tests\DIClasses\Lazy;

class CounterConsumer
{
    public function __construct(public Counter $counter)
    {
    }
}
