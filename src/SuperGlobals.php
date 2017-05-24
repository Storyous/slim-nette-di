<?php

declare(strict_types=1);

namespace SlimNetteDI;

use Nette\SmartObject;

class SuperGlobals
{
    use SmartObject;

    public static function getServer() : array
    {
        return $_SERVER;
    }
}
