<?php

namespace ModuleLoader;

/**
 * @author Daniel Robenek <daniel.robenek@me.com>
 */
class ModuleCompilerExtension extends \Nette\Config\CompilerExtension {

	public $namespaces = array(
		'Commands' => array(),
		'Facades' => array(),
		'Services' => array(),
		'Repositories' => array(),
	);

	protected function getBaseDirectory() {
		return $this->getContainerBuilder()->parameters['appDir'];
	}

	public function getDirectory() {
		return realpath($this->getBaseDirectory() . '/' . ucfirst($this->name) . 'Module');
	}

	public function getNamespace() {
		return ucfirst($this->name) . 'Module';
	}

	public function loadConfiguration() {

		// Services from neon config file
		if (file_exists($this->getDirectory() . '/config.neon')) {
			$this->compiler->parseServices($this->getContainerBuilder(), $this->loadFromFile($this->getDirectory() . '/config.neon'));
		}

		// Load directories
		$namespaceLoader = new NamesaceLoader(new DirectoryLoader($this->getContainerBuilder()), $this->getBaseDirectory());
		foreach ($this->namespaces as $namespace => $setup) {
			$namespaceLoader->loadNamespace($this->getNamespace() . '\\' . $namespace, $setup);
		}
		
		// Load controls
		$controlLoader = new NamesaceLoader(new DirectoryLoader($this->getContainerBuilder(), '/(Facade|Finder)$/'), $this->getBaseDirectory());
		foreach($this->getSubdirectories($this->getDirectory() . '/Controls') as $control) {
			$controlLoader->loadNamespace($this->getNamespace() . '\\Controls\\' . $control);
		}
	}

	protected function getSubdirectories($directory) {
		if (!file_exists($directory)) {
			return array();
		}
		return array_map(function($file) {
							return $file->getFileName();
						}, iterator_to_array(\Nette\Utils\Finder::findDirectories('*')->in($directory)));
	}

}
