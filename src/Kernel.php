<?php
namespace Jalno\GraphQL;

use Illuminate\Contracts\Container\Container;
use GraphQL\{GraphQL, Type\Schema, Executor\ExecutionResult};
use Jalno\GraphQL\Contracts\Kernel as KernelContract;


class Kernel implements KernelContract
{
	protected Container $container;
	protected Schema $schema;

	/**
	 * @property callable
	 */
	protected $resolver;

	public function __construct(Container $container, Schema $schema, ?callable $resolver = null)
	{
		$this->container = $container;
		$this->schema = $schema;
		if (!$resolver) {
			$resolver = $this->container->make(Resolvers\DefaultFieldResolver::class);
		}
		$this->resolver = $resolver;
	}

	public function setSchema(Schema $schema): void
	{
		$this->schema = $schema;
	}

	public function getSchema(): Schema
	{
		return $this->schema;
	}

	public function getResolver(): callable
	{
		return $this->resolver;
	}

	public function setResolver(callable $resolver): void
	{
		$this->resolver = $resolver;
	}

	public function execute(string $query, array $values = []): ExecutionResult
	{
		return GraphQL::executeQuery($this->schema, $query, [], null, $values, null, $this->resolver);
	}
}
