<?php

namespace ByJG\Config\Exception;

use Exception;
use Psr\Container\NotFoundExceptionInterface;

class ConfigException extends Exception implements NotFoundExceptionInterface
{

}
