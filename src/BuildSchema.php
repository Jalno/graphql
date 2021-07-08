<?php
namespace Jalno\GraphQL;

use Exception;
use Iterator;
use Illuminate\Filesystem\Filesystem;
use GraphQL\Type\Schema;
use GraphQL\Language\{Parser, AST\DocumentNode};
use GraphQL\Utils\{SchemaExtender, BuildSchema as GraphQLBuildSchema};

class BuildSchema
{
	protected Filesystem $files;
	protected string $basePath;
	protected string $vendorPath;

	public function __construct(Filesystem $files, string $basePath)
	{
		$this->files = $files;
		$this->basePath = $basePath;
		$this->vendorPath = $basePath . '/vendor';
	}

	public function build(): Schema
	{
		/**
		 * @var Schema|null
		 */
		$schema = null;
		$documents = $this->getAllDocuments();
		foreach ($documents as $document) {
			if ($schema === null) {
				$schema = GraphQLBuildSchema::build($document);
			} else {
				$schema = SchemaExtender::extend($schema, $document);
			}
		}
		if ($schema === null) {
			throw new Exception("there is no documents to build schema");
		}
		return $schema;
	}
	/**
	 * @return Iterator<DocumentNode>
	 */
	protected function getAllDocuments(): Iterator
	{
		foreach ($this->getAllSchemaFiles() as $file) {
			yield $this->parseFile($file);
		}
	}

	protected function parseFile(string $file): DocumentNode
	{
		return Parser::parse($this->files->get($file));
	}

	/**
	 * @return Iterator<string,string>
	 */
	protected function getAllSchemaFiles(): Iterator
	{
		yield from $this->getBuiltinSchemaFiles();
		foreach ($this->getAllPackages() as $package => $config) {
			if (isset($config['schema']) and is_string($config['schema'])) {
				yield $package => $config['install-path'] . "/" . $config['schema'];
			}
		}
	}


	/**
	 * @return Iterator<string,string>
	 */
	protected function getBuiltinSchemaFiles(): \Iterator
	{
		yield "jalno/graphql" => __DIR__ . "/buildin-definations.graphql";
	}

	/**
	 * @return array<string,array{"install-path":string,"schema"?:mixed}>
	 */
	protected function getAllPackages(): array
	{
		/**
		 * @var array<string,array{"install-path":string,"schema"?:mixed}>
		 */
		$packages = [];
		if ($this->files->exists($this->basePath . '/composer.json')) {
			$composer = json_decode($this->files->get($this->basePath . '/composer.json'), true);
			$packages[$composer['name']] = array_merge(['install-path' => $this->basePath], $composer['extra']['graphql'] ?? []);
		}

		$path = $this->vendorPath.'/composer/installed.json';
		if ($this->files->exists($path)) {
            $installed = json_decode($this->files->get($path), true);
            $packages = $installed['packages'] ?? $installed;
        }
		$packages = collect($packages)
			->mapWithKeys(function ($package) {
				return array(
					$package['name'] => array_merge(
						array('install-path' => $this->vendorPath . '/composer/' . $package['install-path']), 
						$package['extra']['graphql'] ?? []
					)
				);
			})
			->all();
		$ignore = array_column($packages, 'dont-discover');
		if (in_array("*", $ignore)) {
			return [];
		}
		return array_filter($packages, fn($package) => !in_array($package, $ignore));
	}
}
