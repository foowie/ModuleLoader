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

	/**
	 * Constructor
	 * @param \Nette\DI\ContainerBuilder $builder
	 * @param string $pattern Pattern for files to load
	 */
	function __construct(\Nette\DI\ContainerBuilder $builder, $pattern = '/^(?!(I[A-Z]|Abstract)).*/') {
		$this->builder = $builder;
		$this->pattern = $pattern;
	}

	/**
	 * Load files in directory
	 * @param string $directory Directory to load
	 * @param string $namespace Namespace of files in current directory (for config naming)
	 * @param string[] $setup Default setup for files
	 * @param string[] $tags Default tags for files
	 * @param bool $recursive Do recursive search
	 * @throws \Nette\InvalidStateException
	 */
	public function loadDirectory($directory, $namespace, array $setup = array(), array $tags = array(), $recursive = true) {
		if (!file_exists($directory)) {
			return;
		}

		if (!is_dir($directory)) {
			throw new \Nette\InvalidStateException('Directory is file!');
		}

		$namespace = trim($namespace, '\\');
		$configNamespace = implode('.', array_map(function($part) {
							return lcfirst($part);
						}, explode('\\', $namespace)));

		foreach (\Nette\Utils\Finder::findFiles('*.php')->in($directory) as $file) {
			$fileName = $this->getFileNameWithoutExt($file->getFileName());
			$configName = $this->formatFileConfigName($fileName, $configNamespace);

			if ($this->isValidServiceName($fileName) && !$this->builder->hasDefinition($configName)) {
				$className = $this->formatClassName($fileName, $namespace);
				if (!$this->isExcluded($className)) {
					$definition = $this->builder->addDefinition($configName)->setClass($className);
					foreach ($setup as $method) {
						$definition->addSetup($method);
					}
					foreach ($tags as $tag) {
						if(is_array($tag)) {
							$definition->addTag($tag[0], $tag[1]);
						} else {
							$definition->addTag($tag);
						}
					}
				}
			}
		}

		if ($recursive) {
			foreach ($this->getSubdirectories($directory) as $directoryName) {
				$subDirectory = $directory . '/' . $directoryName;
				$subNamespace = $namespace . '\\' . $directoryName;
				$this->loadDirectory($subDirectory, $subNamespace);
			}
		}
	}

	/**
	 * Format configuration name
	 * @param string $fileName
	 * @param string $configNamespace
	 * @return string
	 */
	protected function formatFileConfigName($fileName, $configNamespace) {
		return $configNamespace . '.' . lcfirst($fileName);
	}

	/**
	 * Format class name from namespace
	 * @param string $fileName
	 * @param string $namespace
	 * @return string
	 */
	protected function formatClassName($fileName, $namespace) {
		return $namespace . '\\' . ucfirst($fileName);
	}

	/**
	 * Get file name without extension
	 * @param string $fileName
	 * @return string
	 */
	protected function getFileNameWithoutExt($fileName) {
		$dot = strrpos($fileName, '.');
		return ($dot === false) ? $fileName : substr($fileName, 0, $dot);
	}

	/**
	 * Get subrirectory names from given directory
	 * @param string $directory
	 * @return string
	 */
	protected function getSubdirectories($directory) {
		if (!file_exists($directory)) {
			return array();
		}
		return array_map(function($file) {
							return $file->getFileName();
						}, iterator_to_array(\Nette\Utils\Finder::findDirectories('*')->in($directory)));
	}

	/**
	 * Create service from given file name ?
	 * @param string $fileName
	 * @return boolean
	 */
	protected function isValidServiceName($fileName) {
		return (bool) \Nette\Utils\Strings::match($fileName, $this->pattern);
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
