<?php

namespace ByJG\Config\Exception;

use Exception;
use Psr\Container\NotFoundExceptionInterface;

class KeyNotFoundException extends Exception implements NotFoundExceptionInterface
{

}
