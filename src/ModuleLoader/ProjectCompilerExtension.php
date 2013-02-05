<?php

namespace ModuleLoader;

/**
 * @author Daniel Robenek <daniel.robenek@me.com>
 */
class ProjectCompilerExtension extends \Nette\Config\CompilerExtension {

	const RECURSIVE = 'recursive';
	const FILE_PATTERN = 'pattern';
	const SETUP = 'setup';
	const TAGS = 'tags';

	/** @var string regex module pattern */
	protected $moduleDirectoryPattern = '/Module$/';

	/** @var string regex default file pattern */
	protected $defaultFilePattern = '/^(?!(I[A-Z]|Abstract)).*/';

	/** configuration */
	protected $namespaces = array(
		'Handlers' => array(
			self::TAGS => array('handler'),
		),
		'Validators' => array(
			self::TAGS => array('validator'),
		),
		'Facades' => array(),
		'Services' => array(),
		'Repositories' => array(),
		'Controls' => array(
			self::FILE_PATTERN => '/(Facade|Finder)$/'
		),
	);

	/**
	 * Load project
	 */
	public function loadConfiguration() {
		$this->loadModuleRecursive(array());
	}

	/**
	 * Module directory pattern
	 * @return string
	 */
	public function getModuleDirectoryPattern() {
		return $this->moduleDirectoryPattern;
	}

	/**
	 * Module directory pattern
	 */
	public function setModuleDirectoryPattern($moduleDirectoryPattern) {
		$this->moduleDirectoryPattern = $moduleDirectoryPattern;
	}

	/**
	 * Default file pattern
	 * @return string
	 */
	public function getDefaultFilePattern() {
		return $this->defaultFilePattern;
	}

	/**
	 * Default file pattern
	 */
	public function setDefaultFilePattern($defaultFilePattern) {
		$this->defaultFilePattern = $defaultFilePattern;
	}

	/**
	 * Namespaces to load
	 * @return mixed
	 */
	public function getNamespaces() {
		return $this->namespaces;
	}

	/**
	 * Namespaces to load
	 * @param mixed $namespaces
	 */
	public function setNamespaces(array $namespaces) {
		$this->namespaces = $namespaces;
	}

	/**
	 * Load modules recursively
	 * @param string[] $namespaceParts Current namespace parts
	 */
	protected function loadModuleRecursive(array $namespaceParts) {
		$this->loadModule($namespaceParts);
		foreach ($this->getSubdirectories($this->getBaseDirectory() . '/' . implode('/', $namespaceParts)) as $directory) {
			if ($this->isModuleDirectory($directory)) {
				$this->loadModuleRecursive(array_merge($namespaceParts, array($directory)));
			}
		}
	}

	/**
	 * Load single module
	 * @param string[] $namespaceParts
	 */
	protected function loadModule(array $namespaceParts) {
		$directory = $this->getBaseDirectory() . '/' . implode('/', $namespaceParts);
		$currentNamespace = implode('\\', $namespaceParts);

		// Services from neon config file
		if (file_exists($directory . '/config.neon')) {
			$this->compiler->parseServices($this->getContainerBuilder(), $this->loadFromFile($directory . '/config.neon'));
		}

		// Load directories
		foreach ($this->namespaces as $namespace => $config) {
			$recursive = isset($config[self::RECURSIVE]) ? $config[self::RECURSIVE] : true;
			$pattern = isset($config[self::FILE_PATTERN]) ? $config[self::FILE_PATTERN] : $this->defaultFilePattern;
			$setup = isset($config[self::SETUP]) ? $config[self::SETUP] : array();
			$tags = isset($config[self::TAGS]) ? $config[self::TAGS] : array();
			$directoryLoader = new DirectoryLoader($this->getContainerBuilder(), $pattern);
			$directoryLoader->loadDirectory($directory . '/' . $namespace, $currentNamespace . '\\' . $namespace, $setup, $tags, $recursive);
		}
	}

	/**
	 * Get APP_DIR
	 * @return string
	 */
	protected function getBaseDirectory() {
		return $this->getContainerBuilder()->parameters['appDir'];
	}

	/**
	 * Get list of all subdirectory names in given directory
	 * @param string $directory
	 * @return string[]
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
	 * Is given directory valid module name
	 * @param string $directoryName
	 * @return bool
	 */
	protected function isModuleDirectory($directoryName) {
		return (bool) \Nette\Utils\Strings::match($directoryName, $this->moduleDirectoryPattern);
	}

}
