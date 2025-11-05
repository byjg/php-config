<?php

namespace ByJG\Config;

interface ConfigInitializeInterface
{
    public function loadDefinition(?string $env = null): Definition;
}