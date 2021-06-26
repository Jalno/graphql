<?php
namespace Jalno\GraphQL\Http\Controllers;

use Jalno\GraphQL\Contracts\Kernel;
use Illuminate\Http\{Request, JsonResponse};
use GraphQL\Error\DebugFlag;


class GraphqlController
{
	protected Kernel $kernel;

	public function __construct(Kernel $kernel)
	{
		$this->kernel = $kernel;
	}

	public function run(Request $request): JsonResponse
	{
		$query = $request->input("query");
		$variables = $request->input("variables") ?? [];

		if (!$query) {
			return new JsonResponse([
				'errors' => [[
					'message' => 'No query'
				]],
			]);
		}
		$output = array();
		try {
			$result = $this->kernel->execute($query, $variables);

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
		return new JsonResponse($output);
	}
}