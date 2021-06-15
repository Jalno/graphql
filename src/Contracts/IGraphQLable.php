<?php
namespace Jalno\GraphQL\Contracts;

interface IGraphQLable
{

	/**
	 * @return array<class-string>
	 */
	public function getSchemaFiles(): array;

}
