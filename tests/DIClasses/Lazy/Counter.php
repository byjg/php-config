<?php

namespace Tests\DIClasses\Lazy;

class Counter
{
    public static int $created = 0;

    public int $start;

    public readonly string $id;

    /** Untyped, with a default */
    public $label = 'default';

    public function __construct(int $start = 10)
    {
        self::$created++;
        $this->start = $start;
        $this->id = "id-$start";
        $this->label = "label $start";
    }

    public static function fromStart(int $start): static
    {
        return new static($start);
    }

    public function value(): string
    {
        return "counter $this->start";
    }
}
