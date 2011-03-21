<?php
/** 
 * @author noah
 * @date Oct 4, 2010
 * @brief
 * 
*/

namespace Gacela\Model;

abstract class Model implements iModel {

	/**
	 * @var 
	 */
	protected $_changed = array();

	/**
	 * @var stdClass
	 */
	protected $_data;

	protected $_errors = array();

	/**
	 * @var array
	 */
	protected $_fields;

	/**
	 * @var bool
	 */
	protected $_isValidated = false;

	/**
	 * @var bool
	 */
	protected $_isValid = false;

	/**
	 * @var \Gacela\Mapper\Mapper
	 */
	protected $_mapper;

	/**
	 * @var array $_originalData
	 */
	protected $_originalData = array();

	/**
	 * @return \Gacela\Mapper\Mapper
	 */
	protected function _mapper()
	{
		if(!empty($this->_mapper)) return $this->_mapper;

		$class = explode("\\", get_class($this));
		$pos = array_search('Model', $class);

		$class[$pos] = 'Mapper';

		$class = join("\\", $class);

		$this->_mapper = \Gacela::instance()->loadMapper($class);

		return $this->_mapper;
	}

	/**
	 * @param array|stdClass $data
	 */
	public function __construct($data = array())
	{
		if(is_array($data)) {
			$data = (object) $data;
		}

		$this->_fields = $this->_mapper()->getFields();

		if(!isset($data->{key($this->_fields)})) {
			$this->_data = new \stdClass;

			foreach($this->_fields as $field => $meta) {
				$this->_data->$field = $meta->default;
			}
		} else {
			$this->_data = $data;
		}

		$this->init();
	}

	public function __get($key)
	{
		$method = '_get'.ucfirst($key);
		if(method_exists($this, $method)) {
			return $this->$method();
		}
		
		return $this->_data->$key;
	}

	public function __isset($key)
	{
		return isset($this->_data->$key);
	}

	public function __set($key, $val)
	{
		$this->_originalData[$key] = $this->_data->$key;
		$this->_changed[] = $key;
		
		$method = '_set'.ucfirst($key);

		if(method_exists($this, $method)) {
			$this->$method($val);
		} else {
			$this->_data->$key = $val;
		}
	}

	/**
	 * Called at the end of __construct.
	 * Allows developers to add additional stuff to the setup process without
	 * having to directly override the constructor.
	 * 
	 */
	abstract public function init();

	/**
	 * @return bool
	 */
	public function save($data = null)
	{
		if(!$this->validate($data)) {
			return false;
		}

		if($this->_mapper()->save($this->_changed, $this->_data)) {
			// Do Stuff
			
			return true;
		}
		
		return false;
	}

	public function validate($data = null)
	{
		if(!is_null($data)) {
			foreach($data as $key => $val) {
				$this->$key = $val;
			}
		}

		foreach((array) $this->_data as $key => $val) {
			$rs = $this->_fields[$key]->validate($val);

			if(!$rs) {
				$this->_errors[$key] = 'Error Will Robinson!';
			}
		}

		if(count($this->_errors)) {
			return false;
		}

		return true;
	}
}
