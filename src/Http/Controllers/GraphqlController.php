<?php
namespace Jalno\GraphQL\Http\Controllers;

use RuntimeException;
use Jalno\Lumen\Application;
use Laravel\Lumen\Routing\Controller;
use Illuminate\Http\Request;
use Jalno\GraphQL\Contracts\IGraphQLable;
use GraphQL\Utils\{BuildSchema, SchemaExtender};
use GraphQL\Language\Parser;
use GraphQL\Language\AST\DocumentNode;
use GraphQL\GraphQL;
use GraphQL\Error\DebugFlag;
use GraphQL\Type\Definition\ResolveInfo;


class GraphqlController extends Controller {

	protected Application $app;

	public function __construct(Application $app)
	{
		$this->app = $app;
	}

	/**
	 * @return mixed
	 */
	public function run(Request $request)
	{
		$builtinFileContent = file_get_contents(package()->path("buildin-definations.graphql"));
		if ($builtinFileContent === false) {
			throw new \RuntimeException("can not read content of file 'buildin-definations.graphql'");
		}
		$builtin = Parser::parse($builtinFileContent);
		$schema = BuildSchema::build($builtin);
		foreach ($this->app->packages->all() as $package) {
			/** @var \Jalno\Lumen\Contracts\IPackage&IGraphQLable $package */
			if ($package instanceof IGraphQLable) {
				foreach ($package->getSchemaFiles() as $file) {
					$contents = file_get_contents($package->path($file));
					if ($contents === false) {
						throw new \RuntimeException("can not get schema file: '{$package->path($file)}' of package: '{$package->getName()}'");
					}
					$schema = SchemaExtender::extend($schema, Parser::parse($contents));
				}
			}
		}
		$input = $request->json()->all();
		$query = $input['query'];
		$variableValues = $input['variables'] ?? null;

		$output = array();
		try {

			$result = GraphQL::executeQuery($schema, $query, [], null, $variableValues, null, function($objectValue, $args, $context, ResolveInfo $info) use(&$request) {

				if ($info->fieldDefinition->astNode === null) {
					throw new \RuntimeException("the astNode is null");
				}
				$directives = $info->fieldDefinition->astNode->directives;
				$count = $directives->count();

				$controller = $method = null;
				for ($i = 0; $i < $count; $i++) {
					$node = $directives->offsetGet($i);
					// check existence of value,
					// cuz some of '\GraphQL\Language\AST\ValueNode' types do not have value! (like ListValueNode, NullValueNode, ObjectValueNode and VariableNode)
					if (isset($node->name->value) and $node->name->value == "method") {
						$value = $node->arguments->offsetGet(0)->value;
						if (isset($value->value) and is_string($value->value)) {
							[$controller, $method] = explode("@", $value->value, 2);
							break;
						}
					}
				}
				if ($controller and $method) {
					$controller = str_replace("/", "\\", $controller);
				
					if (!class_exists($controller) or !method_exists($controller, $method)) {
						throw new RuntimeException("Api controller or method is not exists");
					}
					$controllerObject = (new $controller);
					if (method_exists($controller, "forUser") and !is_null($request->user())) {
						$controllerObject->forUser($request->user());
					}
					return $controllerObject->$method($args);
				} else {
					return $objectValue->{$info->fieldName};
				}

			});

			$debug = config("app.debug") ? (DebugFlag::INCLUDE_DEBUG_MESSAGE | DebugFlag::INCLUDE_TRACE) : DebugFlag::NONE;
			$output = $result->toArray($debug);
		} catch (\Exception $e) {
			$output = [
				'errors' => [
					[
						'message' => config("app.debug") ? $e->getMessage() : "Internal Error. Please contact support team.",
					]
				]
			];
		}
		return response()->json($output);
	}
}