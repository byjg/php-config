<?php

namespace ByJG\Config;

use ByJG\Config\Exception\KeyNotFoundException;

interface ContainerInterfaceExtended
{
    public function raw(string $id): mixed;

    public function getAsFilename(string $id): string;

    public function keyStatus(string $id): ?KeyStatusEnum;
}
