<?php

namespace Tests\DIClasses\Lazy;

use Override;

class CounterChild extends Counter
{
    #[Override]
    public function value(): string
    {
        return "child $this->start";
    }
}
