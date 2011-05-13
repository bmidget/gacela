<?php
/** 
 * @author Noah Goodrich
 * @date May 12, 2011
 * @brief
 * 
*/

namespace Gacela\Field;

abstract class Field {

	protected $_meta = array();

	public function __construct(array $meta)
	{
		$this->_meta = $meta;
	}

	public function __get($key)
	{
		if(!array_key_exists($key, $this->_meta)) {
				throw new \Exception("Specified key ({$key}) does not exist in field metadata!");
			}

		return $this->_meta[$key];
	}

	/**
	 * @abstract
	 * @param  $value
	 * @return bool
	 */
	abstract public function validate($value);

	/**
	 * @abstract
	 * @param  $value
	 * @param bool $in
	 * @return mixed
	 */
	abstract public function transform($value, $in = true);
}
