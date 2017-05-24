<?php

declare(strict_types=1);

namespace SlimNetteDI;

use Acclimate\Container\ContainerAcclimator;
use Nette\DI\CompilerExtension;
use Nette\DI\Container;
use Nette\DI\Statement;
use Nette\Utils\ArrayHash;
use Psr\Container\ContainerInterface;
use Slim\CallableResolver;
use Slim\Handlers;
use Slim\Http;
use Slim\Router;

class SlimExtension extends CompilerExtension
{
    public $defaults = [
        'httpVersion' => '1.1',
        'responseChunkSize' => 4096,
        'outputBuffering' => 'append',
        'determineRouteBeforeAppMiddleware' => false,
        'displayErrorDetails' => false,
        'addContentLengthHeader' => true,
        'routerCacheFile' => false,
    ];

    public function loadConfiguration()
    {
        $builder = $this->getContainerBuilder();
        $config = $this->validateConfig($this->defaults);

        $builder->addDefinition($this->prefix('container'))
            ->setFactory([new Statement(ContainerAcclimator::class), 'acclimate'], [new Statement('@' . Container::class)]);

        $builder->addDefinition($this->prefix('settings'))
            ->setFactory([ArrayHash::class, 'from'], [$config]);
        $builder->addAlias('settings', $this->prefix('settings'));

        $environment = $builder->addDefinition($this->prefix('environment'))
            ->setClass(Http\Environment::class)
            ->setArguments([new Statement([SuperGlobals::class, 'getServer'])]);
        $builder->addAlias('environment', $this->prefix('environment'));

        $builder->addDefinition($this->prefix('request'))
            ->setFactory([Http\Request::class, 'createFromEnvironment'], [$environment]);
        $builder->addAlias('request', $this->prefix('request'));

        $headers = new Statement(Http\Headers::class, [['Content-Type' => 'text/html; charset=UTF-8']]);
        $builder->addDefinition($this->prefix('response'))
            ->setFactory(Http\Response::class, [200, $headers]);
        $builder->addAlias('response', $this->prefix('response'));

        $builder->addDefinition($this->prefix('router'))
            ->setFactory(Router::class)
            ->addSetup('setCacheFile', [$config['routerCacheFile']])
            ->addSetup('setContainer', [new Statement('@' . ContainerInterface::class)]);
        $builder->addAlias('router', $this->prefix('router'));

        $builder->addDefinition($this->prefix('foundHandler'))
            ->setFactory(Handlers\Strategies\RequestResponse::class);
        $builder->addAlias('foundHandler', $this->prefix('foundHandler'));

        $builder->addDefinition($this->prefix('phpErrorHandler'))
            ->setFactory(Handlers\PhpError::class, [$config['displayErrorDetails']]);
        $builder->addAlias('phpErrorHandler', $this->prefix('phpErrorHandler'));

        $builder->addDefinition($this->prefix('errorHandler'))
            ->setFactory(Handlers\Error::class, [$config['displayErrorDetails']]);
        $builder->addAlias('errorHandler', $this->prefix('errorHandler'));

        $builder->addDefinition($this->prefix('notFoundHandler'))
            ->setFactory(Handlers\NotFound::class);
        $builder->addAlias('notFoundHandler', $this->prefix('notFoundHandler'));

        $builder->addDefinition($this->prefix('notAllowedHandler'))
            ->setFactory(Handlers\NotAllowed::class);
        $builder->addAlias('notAllowedHandler', $this->prefix('notAllowedHandler'));

        $builder->addDefinition($this->prefix('callableResolver'))
            ->setFactory(CallableResolver::class, [new Statement('@' . ContainerInterface::class)]);
        $builder->addAlias('callableResolver', $this->prefix('callableResolver'));
    }
}
