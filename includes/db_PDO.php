<?php
defined('V3S3') or die('access denied');

class db_PDO {
	public $status;
	private $database;
	private $query;
	private $PDO;
	private $PDOstmt;
	private $PDOexception;

	function __construct($host, $username, $password, $database, $buffer=1048576) {
		try {
			$this->PDO = new PDO("mysql:host=".$host.";dbname=" . $database.';charset=utf8mb4', $username, $password);
			$this->PDO->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
			if(defined('PDO::MYSQL_ATTR_MAX_BUFFER_SIZE')) {
				$this->PDO->setAttribute(PDO::MYSQL_ATTR_MAX_BUFFER_SIZE, $buffer);
			}
			$this->database = $database;

			$this->status = 1;
		}catch(PDOException $e) {
			header('Content-type: application/json; charset=utf-8');
			echo json_encode(array('status'=>0, 'code'=>$e->getCode(), 'message'=>$e->getMessage()));
			$this->PDOexception = $e;

			$this->status = 0;
		}
	}

	function use_db($database) {
		$this->query = 'USE `'.$this->quote($database).'`';
		$this->PDOstmt = $this->PDO->prepare($this->query);
		return $this->PDOstmt->execute();
	}

	function prepare($query) {
		$this->query = $query;
		$this->PDOstmt = $this->PDO->prepare($query);

		return $this->PDOstmt;
	}

	function bindParam($param, $value, $type=PDO::PARAM_STR) {
		if(!empty($this->PDOstmt)) {
			return $this->PDOstmt->bindParam($param, $value, $type);
		} else {
			return false;
		}
	}

	function execute() {
		if(!empty($this->PDOstmt)) {
			$result = $this->PDOstmt->execute();
			if(!$result) {
				$this->dumpError();
			}
			return $result;
		} else {
			return false;
		}
	}

	function fetch($fetch_style=PDO::FETCH_ASSOC, $cursor_orientation = PDO::FETCH_ORI_NEXT, $cursor_offset = 0 ) {
		if(!empty($this->PDOstmt)) {
			return $this->PDOstmt->fetch($fetch_style, $cursor_orientation, $cursor_offset);
		} else {
			return false;
		}
	}

	function fetchAll($fetch_style=null, $fetch_argument=null, $ctor_args = null) {
		if(!empty($this->PDOstmt)) {
			if(!is_null($fetch_style)) {
				if(!is_null($fetch_argument)) {
					if(!is_null($ctor_args)) {
						return $this->PDOstmt->fetchAll($fetch_style, $fetch_argument, $ctor_args);
					} else {
						return $this->PDOstmt->fetchAll($fetch_style, $fetch_argument);
					}
				} else {
					return $this->PDOstmt->fetchAll($fetch_style);
				}
			} else {
				return $this->PDOstmt->fetchAll();
			}
		} else {
			return false;
		}
	}

	function fetchAllIndexed($keyInd = '', $splitDuplicateKeys = true) {
		if(!empty($this->PDOstmt)) {
			$resArray = array();
			$sKeyInd = false;
			if(is_array($keyInd)) {
				$sKeyInd = end($keyInd);
				$keyInd = reset($keyInd);
			}
			while($resRow = $this->PDOstmt->fetch(PDO::FETCH_ASSOC)) {
				if (!empty($keyInd)) {

					if ($splitDuplicateKeys) {
						if (isset($resArray[$resRow[$keyInd]]) && is_array($resArray[$resRow[$keyInd]])) {
							if (!empty($sKeyInd)) {
								$resArray[$resRow[$keyInd]][$resRow[$sKeyInd]] = $resRow;
							} else {
								$resArray[$resRow[$keyInd]][] = $resRow;
							}
						} else {
							$resArray[$resRow[$keyInd]] = array();
							if (!empty($sKeyInd)) {
								$resArray[$resRow[$keyInd]][$resRow[$sKeyInd]] = $resRow;
							} else {
								$resArray[$resRow[$keyInd]][] = $resRow;
							}
						}
					} else {
						$resArray[$resRow[$keyInd]] = $resRow;
					}
				} else {
					$resArray[] = $resRow;
				}
			}

			return $resArray;
		} else {
			return false;
		}
	}

	function quote($string) {
		return $this->PDO->quote($string);
	}

	function get_columns($table, $database='') {
		if (empty($table)) {
			return false;
		}
		//var_dump($this->query('select 1 from `'.$table.'`'));die();
		// Renold - added LIMIT 1 for high performance
		$query = 'SELECT 1 FROM `'.$this->database.'`.`'.$table.'` LIMIT 1';
		if(!empty($database)) {
			$query = 'SELECT 1 FROM `'.$database.'`.`'.$table.'` LIMIT 1';
		}
		//var_dump($this->query($query));die();
		$result = false;
		if($this->prepare($query)) {
			if($this->execute()) {
				$result = array();

				$query = 'SHOW COLUMNS FROM `'.$this->database.'`.`'.$table.'`';
				if(!empty($database)) {
					$query = 'SHOW COLUMNS FROM `'.$database.'`.`'.$table.'`';
				}
				$this->prepare($query);
				$this->execute();
				while($field = $this->fetch()) {
					$result[$field['Field']] = $field;
				}
			}
		}
		//tpt_dump($result, true);
		return $result;
	}

	function table_exists($table, $database='') {
		if(!empty($database)) {
			$this->query = 'SELECT 1 FROM `'.$this->quote($database).'`.`'.$this->quote($table).'` LIMIT 1';
		} else {
			$this->query = 'SELECT 1 FROM `'.$this->quote($this->database).'`.`'.$this->quote($table).'` LIMIT 1';
		}
		$this->PDOstmt = $this->PDO->prepare($this->query);
		return $this->PDOstmt->execute();
	}

	function errorCodeConnection() {
		if(!empty($this->PDO)) {
			return $this->PDO->errorCode();
		}

		return false;
	}

	function errorInfoConnection() {
		if(!empty($this->PDO)) {
			return $this->PDO->errorInfo();
		}

		return false;
	}

	function errorCode() {
		if(!empty($this->PDOstmt)) {
			return $this->PDOstmt->errorCode();
		}

		return false;
	}

	function errorInfo() {
		if(!empty($this->PDOstmt)) {
			return $this->PDOstmt->errorInfo();
		} else {
			return false;
		}
	}

	function lastInsertId() {
		if(!empty($this->PDOstmt)) {
			return $this->PDO->lastInsertId();
		} else {
			return false;
		}
	}

	function rowCount() {
		if(!empty($this->PDOstmt)) {
			return $this->PDOstmt->rowCount();
		} else {
			return false;
		}
	}

	function close(){
		unset($this->PDO);
	}
	function __destruct() {
		$this->close();
	}

	function insertData($table, $columns, $values) {
		$pcolumns = array();
		$pvalues = array();

		foreach($values as $key=>$value) {
			if(!empty($columns[$key]) && array_key_exists($key, $values)) {
				$val = $this->validate($value, $columns[$key]);
				if($val !== false) {
					$pcolumns[] = $key;
					$pvalues[] = $val;
				}
			}
		}
		$pcolumns = "\t\t\t".'`'.implode('`,'."\n\t\t\t".'`', $pcolumns).'`';
		$pvalues = "\t\t\t".implode(','."\n\t\t\t".'', $pvalues);
		$query = <<< EOT
INSERT INTO
	`$table`
(
$pcolumns
)
VALUES(
$pvalues
)
EOT;
		$this->query($query);
		$last_id = $this->lastInsertId();

		return $last_id;
	}

	function validate($value, $column, $context='plain') {
		if(!empty($column)) {
			if($context == 'like3') {
				if(is_null($value)) {
					return 'NULL';
				} else if (preg_match('#(blob)|(char)|(text)|(date)#', $column['Type'])) {
					return '"%' . mysql_real_escape_string($value) . '%"';
				} else if (preg_match('#(float)|(double)#', $column['Type'])) {
					return '"%' . floatval($value) . '%"';
				} else {
					return '"%' . intval($value, 10) . '%"';
				}
			} else  {
				if(is_null($value)) {
					return 'NULL';
				} else if (preg_match('#(blob)|(char)|(text)|(date)#', $column['Type'])) {
					return '"' . mysql_real_escape_string($value) . '"';
				} else if (preg_match('#(float)|(double)#', $column['Type'])) {
					return floatval($value);
				} else {
					return intval($value, 10);
				}
			}
		}

		return false;
	}

	function updateData($table, $columns, $values, $where_columns=[], $where_values=[]) {
		$pcolumns = array();
		$pvalues = array();

		foreach($values as $key=>$value) {
			if(!empty($columns[$key]) && array_key_exists($key, $values)) {
				$pcolumns[] = $key;
				if(is_null($value)) {
					$pvalues[] = 'NULL';
				} else if(preg_match('#(blob)|(char)|(text)|(date)#', $columns[$key]['Type'])) {
					$pvalues[] = '"'.mysql_real_escape_string($value).'"';
				} else if(preg_match('#(float)|(double)#', $columns[$key]['Type'])) {
					$pvalues[] = floatval($value);
				} else {
					$pvalues[] = intval($value, 10);
				}
			}
		}

		$data = array_map(
			function($a, $b) {
				return '`'.$a.'`='.$b;
			},
			$pcolumns,
			$pvalues
		);
		$data = implode(', ', $data);

		$f_where_columns = array();
		$f_where_values = array();

		foreach($where_values as $key=>$value) {
			if((!empty($where_columns[$key]) && array_key_exists($key, $where_values))) {
				$f_where_columns[] = $key;
				if(is_null($value)) {
					$f_where_values[] = '=NULL';
				} else if(preg_match('#(blob)|(char)|(text)|(date)#', $where_columns[$key]['Type'])) {
					if(empty($value)) {
						$value = '.*';
					}
					$f_where_values[] = ' REGEXP "'.mysql_real_escape_string($value).'"';
				} else if(preg_match('#(float)|(double)#', $where_columns[$key]['Type'])) {
					$f_where_values[] = '='.floatval($value);
				} else {
					$f_where_values[] = '='.intval($value, 10);
				}
			} else if(preg_match('#([a-zA-Z]+?)(~\\{.*?\\}~)#', $key, $m)) {
				$f_where_columns[] = $m[2];
				if(is_null($value)) {
					$f_where_values[] = '=NULL';
				} else if(preg_match('#(blob)|(char)|(text)|(date)#', $m[1])) {
					if(empty($value)) {
						$value = '.*';
					}

					if((strpos($value, '~{') === 0) && (strpos($value, '}~') !== false)) {
						$f_where_values[] = ' REGEXP '.str_replace('}~', '', str_replace('~{', '', $value));
					} else {
						$f_where_values[] = ' REGEXP "'.mysql_real_escape_string($value).'"';
					}
				} else if(preg_match('#(float)|(double)#', $m[1])) {
					$f_where_values[] = '='.floatval($value);
				} else {
					$f_where_values[] = '='.intval($value, 10);
				}
			}
		}

		$where = array_map(
			function($a, $b) {
				if((strpos($a, '~{') === 0) && (strpos($a, '}~') !== false)) {
					return ''.str_replace('}~', '', str_replace('~{', '', $a)).''.$b;
				}
				return '`'.$a.'`'.$b;
			},
			$f_where_columns,
			$f_where_values
		);

		if(!empty($where)) {
			$where = implode(' AND ', $where);
			$where = <<< EOT
WHERE
	$where
EOT;
		} else {
			$where = '';
		}

		$query = <<< EOT
UPDATE
	`$table`
SET
	$data
$where
EOT;

		$this->prepare($query);
		return $this->execute();
	}

	function selectData($table, $fields, $where_columns=[], $where_values=[], $order_by=null, $limit=null, $indexBy=null, $splitKeys=null) {
		$f_where_columns = array();
		$f_where_values = array();

		foreach($where_values as $key=>$value) {
			if((!empty($where_columns[$key]) && array_key_exists($key, $where_values))) {
				$f_where_columns[] = $key;
				if(is_null($value)) {
					$f_where_values[] = '=NULL';
				} else if(preg_match('#(blob)|(char)|(text)|(date)#', $where_columns[$key]['Type'])) {
					if(empty($value)) {
						$value = '.*';
					}
					$f_where_values[] = ' REGEXP "'.mysql_real_escape_string($value).'"';
				} else if(preg_match('#(float)|(double)#', $where_columns[$key]['Type'])) {
					$f_where_values[] = '='.floatval($value);
				} else {
					$f_where_values[] = '='.intval($value, 10);
				}
			} else if(preg_match('#([a-zA-Z]+?)(~\\{.*?\\}~)#', $key, $m)) {
				$f_where_columns[] = $m[2];
				if(is_null($value)) {
					$f_where_values[] = '=NULL';
				} else if(preg_match('#(blob)|(char)|(text)|(date)#', $m[1])) {
					if(empty($value)) {
						$value = '.*';
					}

					if((strpos($value, '~{') === 0) && (strpos($value, '}~') !== false)) {
						$f_where_values[] = ' REGEXP '.str_replace('}~', '', str_replace('~{', '', $value));
					} else {
						$f_where_values[] = ' REGEXP "'.mysql_real_escape_string($value).'"';
					}
				} else if(preg_match('#(float)|(double)#', $m[1])) {
					$f_where_values[] = '='.floatval($value);
				} else {
					$f_where_values[] = '='.intval($value, 10);
				}
			}
		}

		$where = array_map(
			function($a, $b) {
				if((strpos($a, '~{') === 0) && (strpos($a, '}~') !== false)) {
					return ''.str_replace('}~', '', str_replace('~{', '', $a)).''.$b;
				}
				return '`'.$a.'`'.$b;
			},
			$f_where_columns,
			$f_where_values
		);

		if(!empty($where)) {
			$where = implode(' AND ', $where);
			$where = <<< EOT
WHERE
	$where
EOT;
		} else {
			$where = '';
		}

		if(!is_null($order_by)) {
			$order_by = <<< EOT
ORDER BY
	$order_by
EOT;
		}

		if(!is_null($limit)) {
			$limit = <<< EOT
LIMIT
	$limit
EOT;
		}

		if(is_array($fields)) {
			$fields = '`'.implode('`, `', array_keys($fields)).'`';
		}

		$query = <<< EOT
SELECT
	$fields
FROM
	`$table`
$where
$order_by
$limit
EOT;
		$this->prepare($query);
		$this->execute();

		return $this->fetchAllIndexed($indexBy, $splitKeys);
	}


}