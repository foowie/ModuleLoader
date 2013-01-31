<?php

namespace ModuleLoader;

/**
 * @author Daniel Robenek <daniel.robenek@me.com>
 */
class NamesaceLoader extends \Nette\Object {

	/** @var DirectoryLoader */
	protected $directoryLoader;

	/** @var string */
	protected $baseDirectory;

	/**
	 * Constructior
	 * @param \ModuleLoader\DirectoryLoader $directoryLoader
	 * @param string $baseDirectory Absolute base directory of base namespace
	 */
	function __construct(DirectoryLoader $directoryLoader, $baseDirectory) {
		$this->directoryLoader = $directoryLoader;
		$this->baseDirectory = $baseDirectory;
	}

	/**
	 * Load files in given namespace
	 * @param string $namespace
	 * @param array $setup
	 */
	public function loadNamespace($namespace, array $setup = array(), $recursive = true) {
		$directory = $this->baseDirectory . '/' . str_replace('\\', '/', trim($namespace, '\\'));
		$this->directoryLoader->loadDirectory($directory, $namespace, $setup, $recursive);
	}

}
