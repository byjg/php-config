<?php

namespace ByJG\Config;

enum KeyStatusEnum
{
    case NOT_FOUND;
    case STATIC;
    case IN_MEMORY;
    case WAS_USED;
    case NOT_USED;
}
