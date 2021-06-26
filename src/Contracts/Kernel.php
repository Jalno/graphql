<?php
namespace Jalno\GraphQL\Contracts;

use GraphQL\Executor\ExecutionResult;

interface Kernel
{

	/**
	 * @param array<string,mixed> $values
	 */
	public function execute(string $query, array $values): ExecutionResult;

}
