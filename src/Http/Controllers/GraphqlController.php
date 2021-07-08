<?php
namespace Jalno\GraphQL\Http\Controllers;

use Jalno\GraphQL\Contracts\Kernel;
use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Contracts\Config\Repository as Config;
use GraphQL\Error\DebugFlag;


class GraphqlController
{
	protected Kernel $kernel;
	protected Config $config;

	public function __construct(Kernel $kernel, Config $config)
	{
		$this->kernel = $kernel;
		$this->config = $config;
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
		$debug = $this->config->get("app.debug");
		$output = array();
		try {
			$result = $this->kernel->execute($query, $variables);

			$debugFlag = $debug ? (DebugFlag::INCLUDE_DEBUG_MESSAGE | DebugFlag::INCLUDE_TRACE) : DebugFlag::NONE;
			$output = $result->toArray($debugFlag);
		} catch (\Exception $e) {
			$output = [
				'errors' => [
					[
						'message' => $debug ? $e->getMessage() : "Internal Error. Please contact support team.",
					]
				]
			];
		}
		return new JsonResponse($output);
	}
}