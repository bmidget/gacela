<?php
/** 
 * @author noah
 * @date 2/26/11
 * @brief
 * 
*/

namespace Gacela\DataSource\Resource;

class Database extends Resource {

	/**
	 * @var PDO
	 */
	protected $_db;
	
	public function __construct(array $config)
	{
		$this->_db = $config['db'];
		unset($config['db']);

		parent::__construct($config);

		$method = '_load'.ucfirst($this->_config->dbtype);

		$this->$method();
	}

	private function _loadMysql()
	{
		$this->_meta['columns'] = array();
		$this->_meta['belongs_to'] = array();
		$this->_meta['has_many'] = array();
		$this->_meta['primary'] = array();

		// Setup Column meta information
		$stmt = $this->_db->prepare("DESCRIBE ".$this->_config->name);

		if(!$stmt->execute()) {
			throw new \Exception(
				'Error Code: '.
				$stmt->errorCode().
				'<br/>'.
				\Util::debug($stmt->errorInfo()).
				'Param Dump:'.
				\Util::debug($stmt->debugDumpParams())
			);
		}

		$columns = $stmt->fetchAll(\PDO::FETCH_OBJ);

		foreach($columns as $column) {
			preg_match('/(?P<type>\w+)($|\((?P<length>(\d+|(.*)))\))/', $column->Type, $meta);

			$meta = array_merge(
						array(
							'length' => null,
							'unsigned' => false,
							'sequenced' => false,
							'primary' => false,
							'default' => $column->Default,
							'values' => array()
						),
						$meta
					);
			
			$column->Null == 'No' ? $meta['null'] = true : $meta['null'] = false;

			if($column->Key == 'PRI') {
				$meta['primary'] = true;
			}

			if(stripos($column->Type, 'unsigned') !== false) {
				$meta['unsigned'] = true;
			}

			if(stripos($column->Extra, 'auto_increment') !== false) {
				$meta['sequenced'] = true;
			}

			if($meta['type'] == 'enum') {
				$meta['values'] = explode(',', str_replace("'", "", $meta['length']));
				$meta['length'] = null;
			}

			switch($meta['type']) {
				case 'varchar':
				case 'char':
				case 'text':
				case 'longtext':
					$meta['type'] = 'string';
					break;
				case 'tinyint':
					if($meta['length'] == 1) {
						$meta['type'] = 'bool';
					}
					break;
			}

			$field = "\\Gacela\\Field\\".ucfirst($meta['type']);
			
			$this->_meta['columns'][$column->Field] = new $field($meta);
			
			if($this->_meta['columns'][$column->Field]->primary === true) {
				$this->_meta['primary'][] = $column->Field;
			}
		}
		
		unset($stmt);

		/*
		// Setup Relationships

		// First check for stored procedure used to generate belongs_to relationships
		$stmt = $this->_db->prepare("SHOW PROCEDURE STATUS LIKE :sp");

		$stmt->execute(array(':sp' => 'sp_belongs_to'));

		if($stmt->rowCount()) {
			$sp = $this->_db->prepare("CALL sp_belongs_to (:schema,:table)");
			$sp->execute(array(':schema' => $this->_config->database, ':table' => $this->_config->name));

			$rs = $sp->fetchAll(\PDO::FETCH_OBJ);

			foreach($rs as $row) {
				
			}
		}

		$stmt->execute(array(':sp' => 'sp_has_many'));

		if($stmt->rowCount()) {
			$sp = $this->_db->prepare("CALL sp_has_many (:schema, :table)");
			$sp->execute(array(':schema' => $this->_config->database, ':table' => $this->_config->name));

			$rs = $sp->fetchAll(\PDO::FETCH_OBJ);

			foreach($rs as $row) {
				if(!isset($this->_meta['has_many'][$row->referencedTable])) {
					
				}
				$this->_meta['has_many'][$row->referencedTable] = array($row->keyColumn => $row->referencedColumn);
			}
		}
		 
		 */
	}

	public function getName()
	{
		return $this->_config->name;
	}

	public function getFields()
	{
		return $this->_meta['columns'];
	}

	public function getPrimaryKey()
	{
		return $this->_meta['primary'];
	}

	public function getRelations()
	{
		// @TODO - Have to figure out how to make automatic relationship determination work
	}

}
