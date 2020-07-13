<?php

namespace ByJG\Config\Exception;

use Exception;
use Psr\Container\NotFoundExceptionInterface;

class ConfigNotFoundException extends Exception implements NotFoundExceptionInterface
{

}
