<?php

namespace ByJG\Config;

enum KeyStatusEnum
{
    case NotFound;
    case Value;
    case InMemory;
    case WasUsed;
    case NotUsed;
}
