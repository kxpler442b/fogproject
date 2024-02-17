<?php

use Slim\App;
use Monolog\Logger;
use Slim\Views\PhpRenderer;
use Odan\Session\PhpSession;
use Psr\Log\LoggerInterface;
use Slim\Factory\AppFactory;
use Odan\Session\SessionInterface;
use Monolog\Formatter\LineFormatter;
use Slim\Middleware\ErrorMiddleware;
use Nyholm\Psr7\Factory\Psr17Factory;
use Psr\Container\ContainerInterface;
use App\Infrastructure\Utility\Settings;
use Monolog\Handler\RotatingFileHandler;
use Odan\Session\SessionManagerInterface;
use Slim\Interfaces\RouteParserInterface;
use Selective\BasePath\BasePathMiddleware;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestFactoryInterface;
use App\Core\Middleware\NonFatalErrorHandlerMiddleware;

return [
    'settings' => function () {
        return require __DIR__ . '/settings.php';
    },
    App::class => function (ContainerInterface $container) {
        $app = AppFactory::createFromContainer($container);
        // Register routes
        (require __DIR__ . '/routes.php')($app);

        // Register middleware
        (require __DIR__ . '/middleware.php')($app);

        return $app;
    },
    LoggerInterface::class => function (ContainerInterface $container) {
        $loggerSettings = $container->get('settings')['logger'];

        $logger = new Logger('app');

        // When testing, 'test' value is true which means the monolog test handler should be used
        if (isset($loggerSettings['test']) && $loggerSettings['test'] === true) {
            return $logger->pushHandler(new \Monolog\Handler\TestHandler());
        }

        // Instantiate logger with rotating file handler
        $filename = sprintf('%s/app.log', $loggerSettings['path']);
        $level = $loggerSettings['level'];
        // With the RotatingFileHandler, a new log file is created every day
        $rotatingFileHandler = new RotatingFileHandler($filename, 0, $level, true, 0777);
        // The last "true" here tells monolog to remove empty []'s
        $rotatingFileHandler->setFormatter(new LineFormatter(null, 'Y-m-d H:i:s', false, true));

        return $logger->pushHandler($rotatingFileHandler);
    },

    // HTTP factories
    // For Responder and error middleware
    ResponseFactoryInterface::class => function (ContainerInterface $container) {
        return $container->get(Psr17Factory::class);
    },
    ServerRequestFactoryInterface::class => function (ContainerInterface $container) {
        return $container->get(Psr17Factory::class);
    },

    // For Responder
    RouteParserInterface::class => function (ContainerInterface $container) {
        return $container->get(App::class)->getRouteCollector()->getRouteParser();
    },

    // Error middlewares
    NonFatalErrorHandlerMiddleware::class => function (ContainerInterface $container) {
        $config = $container->get('settings')['error'];
        $logger = $container->get(LoggerInterface::class);

        return new NonFatalErrorHandlerMiddleware(
            (bool)$config['display_error_details'],
            (bool)$config['log_errors'],
            $logger,
        );
    },
    // Set error handler to custom DefaultErrorHandler
    ErrorMiddleware::class => function (ContainerInterface $container) {
        $config = $container->get('settings')['error'];
        $app = $container->get(App::class);

        $logger = $container->get(LoggerInterface::class);

        $errorMiddleware = new ErrorMiddleware(
            $app->getCallableResolver(),
            $app->getResponseFactory(),
            (bool)$config['display_error_details'],
            (bool)$config['log_errors'],
            (bool)$config['log_error_details'],
            $logger
        );

        $errorMiddleware->setDefaultErrorHandler(
            $container->get(\App\Core\Handler\DefaultErrorHandler::class)
        );

        return $errorMiddleware;
    },

    // Database
    
    // Settings
    Settings::class => function (ContainerInterface $container) {
        return new Settings($container->get('settings'));
    },

    // Template renderer
    PhpRenderer::class => function (ContainerInterface $container) {
        $settings = $container->get('settings');
        $rendererSettings = $settings['renderer'];

        /** Global attributes are set in @see PhpRendererMiddleware */
        return new PhpRenderer($rendererSettings['path']);
    },

    // Sessions
    SessionManagerInterface::class => function (ContainerInterface $container) {
        return $container->get(SessionInterface::class);
    },

    SessionInterface::class => function (ContainerInterface $container) {
        $options = $container->get('settings')['session'];

        return new PhpSession($options);
    },

    BasePathMiddleware::class => function (ContainerInterface $container) {
        return new BasePathMiddleware($container->get(App::class));
    }
];