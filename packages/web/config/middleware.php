<?php

use Slim\App;
use Slim\Middleware\ErrorMiddleware;
use Selective\BasePath\BasePathMiddleware;
use Odan\Session\Middleware\SessionStartMiddleware;
use App\Core\Middleware\PhpRendererMiddleware;
use App\Core\Middleware\ForbiddenExceptionMiddleware;
use App\Core\Middleware\ValidationExceptionMiddleware;
use App\Core\Middleware\NonFatalErrorHandlerMiddleware;
use App\Core\Middleware\InvalidOperationExceptionMiddleware;

return function (App $app) {
    $app->addBodyParsingMiddleware();

    // Slim middlewares are LIFO (last in, first out) so when responding, the order is backwards
    // so BasePathMiddleware is invoked before routing and which is before PhpViewExtensionMiddleware

    // Language middleware has to be after PhpViewExtensionMiddleware as it needs the $route parameter
    $app->add(App\Core\Middleware\LocaleMiddleware::class);

    // * Put everything possible before PhpViewExtensionMiddleware as if there is an error in a middleware,
    // * the error page (and layout as well as everything else) needs this middleware loaded to work.
    $app->add(PhpRendererMiddleware::class);

    // Retrieve and store ip address, user agent and user id (has to be BEFORE SessionStartMiddleware as it is using it
    // but after PhpViewExtensionMiddleware as it needs the user id)
    $app->add(App\Core\Middleware\UserNetworkSessionDataMiddleware::class);

    // Has to be after every middleware that needs a started session (LIFO)
    $app->add(SessionStartMiddleware::class);

    // Cors middleware has to be before routing so that it is performed after routing (LIFO)
    // $app->add(CorsMiddleware::class); // Middleware added in api group in routes.php

    // Has to be after phpViewExtensionMiddleware https://www.slimframework.com/docs/v4/cookbook/retrieving-current-route.html
    // The RoutingMiddleware should be added after our CORS middleware
    $app->addRoutingMiddleware();

    // Has to be after Routing (called before on response)
    $app->add(BasePathMiddleware::class);

    // Error middlewares should be added last as they will be the first on the response (LIFO).
    $app->add(ValidationExceptionMiddleware::class);
    $app->add(ForbiddenExceptionMiddleware::class);
    $app->add(InvalidOperationExceptionMiddleware::class);

    // Handle and log notices and warnings (throws ErrorException if displayErrorDetails is true)
    $app->add(NonFatalErrorHandlerMiddleware::class);
    // Set error handler to custom DefaultErrorHandler (defined in container.php)
    $app->add(ErrorMiddleware::class);
};