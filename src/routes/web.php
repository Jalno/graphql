<?php

use Jalno\GraphQL\Http\Controllers\GraphqlController;

/** @var \Laravel\Lumen\Routing\Router $router */

$router->addRoute(["GET", "POST"], "/graphql", array('uses' => GraphqlController::class."@run"));
