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

	function __construct(DirectoryLoader $directoryLoader, $baseDirectory) {
		$this->directoryLoader = $directoryLoader;
		$this->baseDirectory = $baseDirectory;
	}

	public function loadNamespace($namespace, array $setup = array()) {
		$directory = $this->baseDirectory . '/' . str_replace('\\', '/', trim($namespace, '\\'));
		$this->directoryLoader->loadDirectory($directory, $namespace, $setup);
	}

}
