<?php
namespace Jalno\GraphQL;

use Illuminate\Support\ServiceProvider;
use Illuminate\Filesystem\Filesystem;


class GraphQLServiceProvider extends ServiceProvider
{
	public function register(): void
	{
		$this->app->singleton(BuildSchema::class, function($app) {
			return new BuildSchema($app->make(Filesystem::class), $app->basePath());
		});
		$this->app->singleton(Contracts\Kernel::class, function($app) {
			return new Kernel($app->make(BuildSchema::class)->build());
		});
	}

	public function boot(): void
	{
		$this->loadRoutesFrom(__DIR__ . '/../routes/web.php');
	}

}
