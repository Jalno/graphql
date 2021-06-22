<?php
namespace Jalno\GraphQL\Contracts;

use Jalno\Lumen\Contracts\IPackage;

interface IGraphQLable extends IPackage
{

	/**
	 * @return string[]
	 */
	public function getSchemaFiles(): array;

}
