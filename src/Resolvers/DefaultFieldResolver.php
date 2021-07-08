<?php
namespace Jalno\GraphQL\Resolvers;

use RuntimeException;
use Illuminate\Contracts\Container\Container;
use GraphQL\Type\Definition\ResolveInfo;


class DefaultFieldResolver
{
	protected Container $container;

	public function __construct(Container $container)
	{
		$this->container = $container;
	}

	/**
	 * @return mixed
	 */
	public function __invoke($objectValue, $args, $context, ResolveInfo $info)
	{
		$controller = $this->findController($info);
		if ($controller) {
			return $this->callController($controller);
		}
		return $objectValue->{$info->fieldName};
	}

	protected function findController(ResolveInfo $info): ?string
	{
		if ($info->fieldDefinition->astNode === null) {
			return null;
		}
		$directives = $info->fieldDefinition->astNode->directives;
		for ($i = 0, $l = $directives->count(); $i < $l; $i++) {
			$node = $directives->offsetGet($i);
			if (isset($node->name->value) and $node->name->value == "method") {
				$value = $node->arguments->offsetGet(0)->value;
				if (isset($value->value) and is_string($value->value)) {
					return $value->value;
				}
			}
		}
	}

	/**
	 * @return mixed
	 */
	protected function callController($callable)
	{
		if (is_string($uses) and strpos($callable, '@') === false) {
            $callable .= "@__invoke";
        }
		[$controller, $method] = explode("@", $callable, 2);
		$instance = $this->container->make($controller);
		if (!method_exists($instance, $method)) {
            throw new RuntimeException("Api controller or method is not exists");
        }
		return $this->container->call([$instance, $method], ["args" => $args]);
	}
}
