<?php

namespace Tests\DIClasses;

class MixedDependencies
{
    protected Random $random;
    protected string $apiKey;
    protected Area $area;
    protected int $maxRetries;

    public function __construct(Random $random, string $apiKey, Area $area, int $maxRetries = 3)
    {
        $this->random = $random;
        $this->apiKey = $apiKey;
        $this->area = $area;
        $this->maxRetries = $maxRetries;
    }

    public function getRandomNumber(): int
    {
        return $this->random->getNumber();
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function getArea(): Area
    {
        return $this->area;
    }

    public function getMaxRetries(): int
    {
        return $this->maxRetries;
    }
}
