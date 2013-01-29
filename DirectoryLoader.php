<?php

namespace ModuleLoader;

/**
 * @author Daniel Robenek <daniel.robenek@me.com>
 */
class DirectoryLoader extends \Nette\Object {

	/** @var \Nette\DI\ContainerBuilder */
	protected $builder;

	/** @var string Pattern for file name */
	protected $pattern;
	
	function __construct(\Nette\DI\ContainerBuilder $builder, $pattern = '/^(?!(Base|I[A-Z]|Abstract)).*/') {
		$this->builder = $builder;
		$this->pattern = $pattern;
	}

	public function loadDirectory($directory, $namespace, array $setup = array()) {
		if (!file_exists($directory)) {
			return;
		}

		if (!is_dir($directory)) {
			throw new \Nette\InvalidStateException('Directory is file!');
		}

		$namespace = trim($namespace, '\\');
		$configNamespace = implode('.', array_map(function($part) {return lcfirst($part);}, explode('\\', $namespace)));

		foreach (\Nette\Utils\Finder::findFiles('*.php')->from($directory) as $file) {
			$fileName = $this->getFileNameWithoutExt($file->getFileName());
			$configName = $this->formatFileConfigName($fileName, $configNamespace);

			if ($this->isValidServiceName($fileName) && !$this->builder->hasDefinition($configName)) {
				$className = $this->formatClassName($fileName, $namespace);
				if (!$this->isExcluded($className)) {
					$definition = $this->builder->addDefinition($configName)->setClass($className);
					foreach ($setup as $method) {
						$definition->addSetup($method);
					}
				}
			}
		}
	}

	protected function formatFileConfigName($fileName, $configNamespace) {
		return $configNamespace . '.' . lcfirst($fileName);
	}

	protected function formatClassName($fileName, $namespace) {
		return $namespace . '\\' . ucfirst($fileName);
	}

	protected function getFileNameWithoutExt($fileName) {
		$dot = strrpos($fileName, '.');
		return ($dot === false) ? $fileName : substr($fileName, 0, $dot);
	}

	/**
	 * Create service from given file name ?
	 * @param string $fileName
	 * @return boolean
	 */
	protected function isValidServiceName($fileName) {
		return (bool)\Nette\Utils\Strings::match($fileName, $this->pattern);
	}

	/**
	 * Tests if given class should be excluded from registering in container
	 * @param  string $className
	 * @return boolean
	 */
	protected function isExcluded($className) {
		$classReflection = new \Nette\Reflection\ClassType($className);
		return $classReflection->hasAnnotation('exclude');
	}

}
