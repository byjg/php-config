<?php

namespace ByJG\Config\Exception;

use Exception;
use Psr\Container\NotFoundExceptionInterface;

class EnvironmentException extends Exception implements NotFoundExceptionInterface
{

}
