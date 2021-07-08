<?php

use Illuminate\Container\Container;
use Jalno\GraphQL\Http\Controllers\GraphqlController;

/** @var \Illuminate\Contracts\Routing\Registrar $router */
$router = Container::getInstance()->make("router");

$router->get("/graphql", array('uses' => GraphqlController::class."@run"));
$router->post("/graphql", array('uses' => GraphqlController::class."@run"));
