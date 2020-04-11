<?php

/**
 * PhpStorm code completion
 *
 * Add code completion for PSR-11 Container Interface and more...
 */

namespace PHPSTORM_META {

    use Interop\Container\ContainerInterface as InteropContainerInterface;
    use Psr\Container\ContainerInterface as PsrContainerInterface;
    use Psr\Http\Message\ServerRequestInterface;
    use PSR7Session\Http\SessionMiddleware;
    use PSR7Session\Session\SessionInterface;

    // Old Interop\Container\ContainerInterface
    override(InteropContainerInterface::get(0),
        map([
            '' => '@',
        ])
    );

    // PSR-11 Container Interface
    override(PsrContainerInterface::get(0),
        map([
            '' => '@',
        ])
    );

    // PSR-7 requests attributes; e.g. PSR-7 Storage-less HTTP Session
    override(ServerRequestInterface::getAttribute(0),
        map([
            SessionMiddleware::SESSION_ATTRIBUTE instanceof SessionInterface,
        ])
    );
}
