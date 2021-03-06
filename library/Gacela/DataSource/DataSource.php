<?php
/**
 * @author Noah Goodrich
 * @date May 7, 2011
 *
 *
*/

namespace Gacela\DataSource;

abstract class DataSource implements iDataSource {

	protected $_config = array();

	protected $_resources = array();

	protected $_driver;

	protected $_lastQuery = array();

	protected function _cache($name, $key, $data = null)
	{
		$instance = $this->_singleton();

		$version = $instance->cache($name . '_version');

		if (is_null($version) || $version === false) {
			$version = 0;
			$instance->cache($name . '_version', $version);
		}

		$key = 'query_' . $version . '_' . $key;

		$cached = $instance->cache($key);

		if (is_null($data)) {
			return $cached;
		}

		if ($cached === false) {
			$instance->cache($key, $data);
		} else {
			$instance->cache($key, $data, true);
		}
	}

	protected function _incrementCache($name)
	{
		$instance = $this->_singleton();

		$cached = $instance->cache($name.'_version');

		if($cached === false) {
			return;
		}

		$instance->incrementCache($name.'_version');
	}

	protected function _driver()
	{
		if(empty($this->_driver))
		{
			$adapter = $this->_singleton()->autoload("\\DataSource\\Adapter\\".ucfirst($this->_config->type));

			$this->_driver = new $adapter($this->_config);
		}

		return $this->_driver;
	}

	protected function _setLastQuery($query, $args = null)
	{
		if($query instanceof Query\Query)  {
			// Using the _lastQuery variable so that we can see the query when debugging
			list($this->_lastQuery['query'], $this->_lastQuery['args']) = $query->assemble();
		} else {
			$this->_lastQuery = array('query' => $query, 'args' => $args);
		}

		return hash('whirlpool', serialize(array($this->_lastQuery['query'], $this->_lastQuery['args'])));
	}

	protected function _singleton()
	{
		return \Gacela::instance();
	}

	public function __construct(array $config)
	{
		$this->_config = (object) $config;
	}

	public function beginTransaction()
	{
		return false;
	}

	public function createConfig($name, $fields, $write = false)
	{
		$resource = $this->loadResource($name, true);

		$resource = (array) $resource;

		$resource = array_shift($resource);

		foreach($resource['columns'] as $field => $meta)
		{
			if(!in_array($field, $fields)) {
				unset($resource['columns'][$field]);
			}
			else {
				foreach($meta as $k => $m) {
					if(is_object($m)) {
						$meta[$k] = (array) $m;
					}
				}

				$resource['columns'][$field] = (array) $meta;
			}
		}

		$string = "<?php \n\n return ".var_export($resource, true);

		$string .= ';';

		if($write) {
			$handle = fopen($this->_singleton()->configPath().$name.'.php', 'w+');

			if(!fwrite($handle, $string)) {
				return false;
			}
		}

		return $string;
	}

	public function commitTransaction()
	{
		return false;
	}

	public function lastQuery()
	{
		return $this->_lastQuery;
	}

	/**
	 * @see \Gacela\DataSource\iDataSource::loadResource()
	 */
	public function loadResource($name, $force = false)
	{
		$cached = $this->_singleton()->cache('resource_'.$name);

		if($cached === false || is_null($cached))  {
			$cached = new Resource($this->_driver()->load($name, $force));

			$this->_singleton()->cache('resource_'.$name, $cached);
		}

		return $cached;
	}

	public function rollbackTransaction()
	{
		return false;
	}
}

