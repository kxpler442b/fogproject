<?php

use Slim\App;
use Slim\Routing\RouteCollectorProxy;
use Slim\Exception\HttpNotFoundException;
use App\Application\Middleware\CorsMiddleware;
use App\Application\Middleware\UserAuthenticationMiddleware;

return function (App $app) {
    

    //    $app->get( '/favicon.ico', function ($request, $response) {
    //        $response->getBody()->write('https://samuel-gfeller.ch/wp-content/uploads/2020/08/cropped-favicon_small-32x32.png');
    //
    //        return $response;
    //    });

    /**
     * Catch-all route to serve a 404 Not Found page if none of the routes match
     * NOTE: make sure this route is defined last.
     * //     */
    $app->map(
        ['GET', 'POST', 'PUT', 'DELETE', 'PATCH'],
        '/{routes:.+}',
        function ($request, $response) {
            throw new HttpNotFoundException(
                $request,
                'Route "' . $request->getUri()->getHost() . $request->getUri()->getPath() .
                '" not found.'
                // <br>Basepath: "' . $app->getBasePath() . '"'
            );
        }
    );
};
