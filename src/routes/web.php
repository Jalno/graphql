<?php

use Jalno\GraphQL\Http\Controllers\GraphqlController;

/** @var \Laravel\Lumen\Routing\Router|\Illuminate\Contracts\Routing\Registrar $router */
$router = app()->router;

$router->get("/graphql", array('uses' => GraphqlController::class."@run"));
$router->post("/graphql", array('uses' => GraphqlController::class."@run"));
