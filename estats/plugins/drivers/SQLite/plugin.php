<?php
/**
 * SQLite database driver class for eStats
 * @author Emdek <http://emdek.pl>
 * @version 4.0.02
 */

class EstatsDriverSqlite extends EstatsDriver
{

/**
 * Returns filed name string
 * @param string Field
 * @return string
 */

	private function fieldString($Field)
	{
		if (preg_match('#^.+\.#', $Field) > 0)
		{
			$Position = strpos($Field, '.');
			$Table = substr($Field, 0, $Position);
			$Field = substr($Field, ($Position + 1));

			return '"'.$Table.'".'.(($Field == '*')?'*':'"'.$Field.'"');
		}
		else
		{
			return '"'.$Field.'"';
		}
	}

/**
 * Returns operator string
 * @param integer Operator
 * @return string
 */

	private function operatorString($Operator)
	{
		if ($Operator == self::OPERATOR_NOT)
		{
			return 'NOT';
		}
		else
		{
			$Not = ($Operator & self::OPERATOR_NOT);
			$Operator = ($Operator & ~self::OPERATOR_NOT);

			switch ($Operator)
			{
				case self::OPERATOR_AND:
					return 'AND';
				case self::OPERATOR_OR:
					return 'OR';
				case self::OPERATOR_EQUAL:
					return ($Not?'!':'').'=';
				case self::OPERATOR_REGEXP:
					return 'REGEXP';
				case self::OPERATOR_LIKE:
					return ($Not?'NOT ':'').'LIKE';
				case self::OPERATOR_GREATER:
					return '>';
				case self::OPERATOR_GREATEROREQUAL:
					return '>=';
				case self::OPERATOR_LESS:
					return '<';
				case self::OPERATOR_LESSOREQUAL:
					return '<=';
				case self::OPERATOR_ISNULL:
					return 'IS '.($Not?'NOT ':'').'NULL';
				case self::OPERATOR_PLUS:
					return '+';
				case self::OPERATOR_MINUS:
					return '-';
				case self::OPERATOR_INCREASE:
					return '+ 1';
				case self::OPERATOR_DECREASE:
					return '- 1';
				case self::OPERATOR_MULTIPLICATION:
					return '*';
				case self::OPERATOR_DIVISION:
					return '/';
				case self::OPERATOR_GROUPING_START:
					return '(';
				case self::OPERATOR_GROUPING_END:
					return ')';
				default:
					return '';
			}
		}
	}

/**
 * Returns element string
 * @param integer Element
 * @param array Data
 * @return string
 */

	private function elementString($Element, $Data)
	{
		switch ($Element)
		{
			case self::ELEMENT_FIELD:
				return $this->fieldString($Data);
			case self::ELEMENT_VALUE:
				return $this->PDO->quote($Data);
			case self::ELEMENT_FUNCTION:
				if ($Data[0] == self::FUNCTION_COUNT)
				{
					return 'COUNT('.($Data[1]?$this->fieldString($Data[1]):'*').')';
				}
				else if ($Data[0] == self::FUNCTION_DATETIME)
				{
					return 'STRFTIME('.$this->PDO->quote($Data[1][1]).', '.$this->fieldString($Data[1][0]).')';
				}
				else
				{
					if (is_array($Data[1]))
					{
						$Data[1] = $this->elementString($Data[1][0], $Data[1][1]);
					}
					else
					{
						$Data[1] = $this->fieldString($Data[1]);
					}

					switch ($Data[0])
					{
						case self::FUNCTION_SUM:
							return 'SUM('.$Data[1].')';
						case self::FUNCTION_MIN:
							return 'MIN('.$Data[1].')';
						case self::FUNCTION_MAX:
							return 'MAX('.$Data[1].')';
						case self::FUNCTION_AVG:
							return 'AVG('.$Data[1].')';
						case self::FUNCTION_TIMESTAMP:
							return 'STRFTIME("%s", '.$Data[1].')';
						default:
							return '';
					}
				}
			case self::ELEMENT_OPERATION:
				if ($Data[1] & self::OPERATOR_BETWEEN)
				{
					return (is_array($Data[0])?$this->elementString($Data[0][0], $Data[0][1]):$this->PDO->quote($Data[2])).' '.(($Data[1] & self::OPERATOR_NOT)?'NOT ':'').'BETWEEN '.$this->fieldString($Data[2]).' AND '.$this->fieldString($Data[3]);
				}
				else if ($Data[1] & self::OPERATOR_IN)
				{
					$Items = array();

					for ($i = 0, $c = count($Data[2]); $i < $c; ++$i)
					{
						$Items[] = $this->PDO->quote($Data[2][$i]);
					}

					return $this->fieldString($Data[0]).' '.(($Data[1] & self::OPERATOR_NOT)?'NOT ':'').'IN('.implode(', ', $Items).')';
				}
				else
				{
					return (is_array($Data[0])?$this->elementString($Data[0][0], $Data[0][1]):$this->fieldString($Data[0])).' '.$this->operatorString($Data[1]).(isset($Data[2])?' '.(is_array($Data[2])?$this->elementString($Data[2][0], $Data[2][1]):$this->PDO->quote($Data[2])):'');
				}
			case self::ELEMENT_EXPRESSION:
				$String = '';

				for ($i = 0, $c = count($Data); $i < $c; ++$i)
				{
					if (is_array($Data[$i]))
					{
						$String.= $this->elementString($Data[$i][0], $Data[$i][1]);
					}
					else if (is_int($Data[$i]))
					{
						if ($Data[$i] == self::OPERATOR_GROUPING_START || $Data[$i] == self::OPERATOR_GROUPING_END)
						{
							$String.= $this->operatorString($Data[$i]);
						}
						else
						{
							$String.= ' '.$this->operatorString($Data[$i]).' ';
						}
					}
					else
					{
						$String.= $this->fieldString($Data[$i]);
					}
				}

				return $String;
			case self::ELEMENT_CONCATENATION:
				$Parts = array();

				for ($i = 0, $c = count($Data); $i < $c; ++$i)
				{
					if (is_array($Data[$i]))
					{
						$Parts[] = $this->elementString($Data[$i][0], $Data[$i][1]);
					}
					else
					{
						$Parts[] = $this->fieldString($Data[$i]);
					}
				}

				return implode(' || ', $Parts);
			case self::ELEMENT_CASE:
				$Parts = array();

				for ($i = 0, $c = count($Data); $i < $c; ++$i)
				{
					if (isset($Data[$i][1]))
					{
						$Parts[] = 'WHEN '.(is_array($Data[$i][0])?$this->elementString($Data[$i][0][0], $Data[$i][0][1]):$this->fieldString($Data[$i][0])).' THEN '.(is_array($Data[$i][1])?$this->elementString($Data[$i][1][0], $Data[$i][1][1]):$this->fieldString($Data[$i][1]));
					}
					else
					{
						$Parts[] = 'ELSE '.(is_array($Data[$i][0])?$this->elementString($Data[$i][0][0], $Data[$i][0][1]):$this->fieldString($Data[$i][0]));
					}
				}

				return 'CASE '.implode(' ', $Parts).' END';
			CASE self::ELEMENT_SUBQUERY:
				return ('('.self::selectData($Data[0], (isset($Data[1])?$Data[1]:NULL), (isset($Data[2])?$Data[2]:NULL), (isset($Data[3])?$Data[3]:0), (isset($Data[4])?$Data[4]:0), (isset($Data[5])?$Data[5]:NULL), (isset($Data[6])?$Data[6]:NULL), (isset($Data[7])?$Data[7]:NULL), (isset($Data[8])?$Data[8]:FALSE), self::RETURN_QUERY).')');
			default:
				return '';
		}
	}

/**
 * Returns TRUE if driver is available
 * @return boolean
 */

	public function isAvailable()
	{
		return in_array('sqlite', PDO::getAvailableDrivers());
	}

/**
 * Generates connection string
 * @param array Parameters
 * @return string
 */

	public function connectionString($Parameters)
	{
		return 'sqlite:'.realpath(dirname($_SERVER['SCRIPT_FILENAME'])).'/data/estats_'.md5(uniqid(mt_rand(0, 1000000000))).'.sqlite';
	}

/**
 * Returns option value
 * @param string Option
 * @return string
 */

	public function option($Option)
	{
		if (!$this->Information || count($this->Information) < 2)
		{
			$Information =  parse_ini_file(dirname(__FILE__).'/plugin.ini', TRUE);
			$this->Information = &$Information['Information'];
		}

		return (isset($this->Information[$Option])?$this->Information[$Option]:'');
	}

/**
 * Connects to the database
 * @param string Connection
 * @param string User
 * @param string Password
 * @param string Prefix
 * @param boolean Persistent
 * @return boolean
 */

	public function connect($Connection, $User, $Password, $Prefix = '', $Persistent = FALSE)
	{
		if (parent::connect($Connection, $User, $Password, $Prefix, $Persistent))
		{
			$this->Information['DatabaseVersion'] = $this->PDO->getAttribute(PDO::ATTR_SERVER_VERSION);
		}

		return $this->Connected;
	}

/**
 * Creates database table
 * @param string Table
 * @param array Atrributes
 * @param boolean Replace
 * @return boolean
 */

	public function createTable($Table, $Attributes, $Replace = FALSE)
	{
		$Parts = $PrimaryKeys = $ForeignKeys = $IndexKeys = $Constraints = array();

		if ($this->tableExists($Table))
		{
			if ($Replace)
			{
				deleteTable($Table);
			}
			else
			{
				return FALSE;
			}
		}

		foreach ($Attributes as $Key => $Value)
		{
			$SQL = '"'.$Key.'" '.$Value['type'].(isset($Value['length'])?'('.$Value['length'].')':'').(isset($Value['null'])?'':' NOT NULL').(isset($Value['autoincrement'])?' AUTOINCREMENT':'');

			if (isset($Value['unique']))
			{
				if ($Value['unique'] !== 'TRUE')
				{
					if (isset($Constraints[$Value['unique']]))
					{
						$Constraints[$Value['unique']][1][] = $Key;
					}
					else
					{
						$Constraints[$Value['unique']] = array('UNIQUE', array($Key));
					}
				}
				else
				{
					$SQL.= ' UNIQUE';
				}
			}
			else if (isset($Value['default']))
			{
				$SQL.= ' DEFAULT '.$this->PDO->quote($Value['default']);
			}

			$Parts[] = $SQL;

			if (isset($Value['primary']))
			{
				$PrimaryKeys[] = '"'.$Key.'"';
			}

			if (isset($Value['foreign']))
			{
				$Field = explode('.', $Value['foreign']);
				$ForeignKeys[] = 'FOREIGN KEY("'.$Key.'") REFERENCES "'.$this->Prefix.$Field[0].'" ("'.$Field[1].'")'.(isset($Value['onupdate'])?' ON UPDATE '.$Value['onupdate']:'').(isset($Value['ondelete'])?' ON DELETE '.$Value['ondelete']:'');
			}

			if (isset($Value['index']) && !isset($Value['unique']))
			{
				$IndexKeys[] = $Key;
			}
		}

		if ($PrimaryKeys)
		{
			$Parts[] = 'PRIMARY KEY('.implode (', ', $PrimaryKeys).')';
		}

		foreach ($Constraints as $Key => $Value)
		{
			if ($Value[0] == 'UNIQUE')
			{
				$Parts[] = 'UNIQUE(`'.implode('`, `', $Value[1]).'`)';
			}
		}

		$Parts = array_merge($Parts, $ForeignKeys);

		$this->PDO->exec('CREATE TABLE "'.$this->Prefix.$Table.'" ('.implode(', ', $Parts).')');

		for ($i = 0, $c = count($IndexKeys); $i < $c; ++$i)
		{
			$this->PDO->exec('CREATE INDEX "'.$this->Prefix.$Table.'_'.$IndexKeys[$i].'_index" ON "'.$this->Prefix.$Table.'" ("'.$IndexKeys[$i].'")');
		}

		return $this->tableExists($Table);
	}

/**
 * Deletes database table
 * @param string Table
 * @return boolean
 */

	public function deleteTable($Table)
	{
		$this->PDO->exec('DROP TABLE "'.$this->Prefix.$Table.'"');

		return !$this->tableExists($Table);
	}

/**
 * Checks if database table exists
 * @param string Table
 * @return boolean
 */

	public function tableExists($Table)
	{
		$Result = $this->PDO->query('SELECT "name" FROM "sqlite_master" WHERE "name" = '.$this->PDO->quote($this->Prefix.$Table).' AND "type" = \'table\'');

		return ($Result?(strlen($Result->fetchColumn(0)) > 1):0);
	}

/**
 * Returns database table size in bytes or FALSE if failed
 * @param string Table
 * @return integer
 */

	public function tableSize($Table)
	{
		$Result = $this->PDO->query('PRAGMA TABLE_INFO('.$this->PDO->quote($this->Prefix.$Table).')');

		if (!$Result)
		{
			return FALSE;
		}

		$Array = $Result->fetchAll(PDO::FETCH_ASSOC);
		$Parts = array();

		for ($i = 0, $c = count($Array); $i < $c; ++$i)
		{
			$Parts[] = 'SUM(LENGTH("'.$Array[$i]['name'].'"))';
		}

		$Result = $this->PDO->query('SELECT ('.implode(' + ', $Parts).') FROM '.$this->PDO->quote($this->Prefix.$Table));

		return ($Result?$Result->fetchColumn(0):FALSE);
	}

/**
 * Retrieves data from database table
 * @param array Tables
 * @param array Fields
 * @param array Where
 * @param integer Amount
 * @param integer Offset
 * @param array OrderBy
 * @param array GroupBy
 * @param array Having
 * @param boolean Distinct
 * @param integer Mode
 * @return mixed
 */

	public function selectData($Tables, $Fields = NULL, $Where = NULL, $Amount = 0, $Offset = 0, $OrderBy = NULL, $GroupBy = NULL, $Having = NULL, $Distinct = FALSE, $Mode = self::RETURN_RESULT)
	{
		if (is_array($Fields))
		{
			$Parts = array();

			for ($i = 0, $c = count($Fields); $i < $c; ++$i)
			{
				if ($Fields[$i] == self::FUNCTION_COUNT)
				{
					$Parts[] = 'COUNT(*)';
				}
				else if (is_array($Fields[$i]))
				{
					$Parts[] = $this->elementString($Fields[$i][0], $Fields[$i][1]).(empty($Fields[$i][2])?'':' AS "'.$Fields[$i][2].'"');
				}
				else
				{
					$Parts[] = $this->fieldString($Fields[$i]).(strstr($Fields[$i], '.')?'':' AS "'.$Fields[$i].'"');
				}
			}

			$FieldsPart = implode(', ', $Parts);
		}
		else
		{
			$FieldsPart = '*';
		}

		$TablesPart = '';

		for ($i = 0, $c = count($Tables); $i < $c; ++$i)
		{
			if (is_array($Tables[$i]))
			{
				if (is_int($Tables[$i][0]))
				{
					$Natural = ($Tables[$i][0] & self::JOIN_NATURAL);
					$Tables[$i][0] = ($Tables[$i][0] & ~self::JOIN_NATURAL);
					$TablesPart.= ($Natural?' NATURAL':'').' ';

					switch ($Tables[$i][0])
					{
						case self::JOIN_CROSS:
							$TablesPart.= 'CROSS';
						break;
						case self::JOIN_LEFT:
							$TablesPart.= 'LEFT';
						break;
						case self::JOIN_RIGHT:
							$TablesPart.= 'RIGHT';
						break;
						case self::JOIN_FULL:
							$TablesPart.= 'FULL';
						break;
						default:
							$TablesPart.= 'INNER';
						break;
					}

					$TablesPart.= ' JOIN ';
				}
				else
				{
					$TablesPart.= '"'.$this->Prefix.$Tables[$i][0].'" AS "'.$Tables[$i][1].'"';
				}
			}
			else
			{
				$TablesPart.= '"'.$this->Prefix.$Tables[$i].'"'.($this->Prefix?' AS "'.$Tables[$i].'"':'');
			}

			if ($i > 0 && is_array($Tables[$i - 1]) && is_int($Tables[$i - 1][0]))
			{
				if ($Tables[$i - 1][1] == self::OPERATOR_JOIN_ON)
				{
					$TablesPart.= ' ON '.$this->elementString(self::ELEMENT_EXPRESSION, $Tables[$i - 1][2]).' ';
				}
				else
				{
					for ($j = 0, $c = count($Tables[$i - 1][2]); $j < $c; ++$j)
					{
						if (is_array($Tables[$i - 1][2][$j]))
						{
							$Tables[$i - 1][2][$j] = $this->elementString($Tables[$i - 1][2][$j][0], $Tables[$i - 1][2][$j][1]);
						}
						else
						{
							$Tables[$i - 1][2][$j] = $this->fieldString($Tables[$i - 1][2][$j]);
						}
					}

					$TablesPart.= ' USING('.implode(', ', $Tables[$i - 1][2]).') ';
				}
			}
		}

		if (is_array($OrderBy))
		{
			foreach ($OrderBy as $Key => $Value)
			{
				if (is_array($Value))
				{
					$OrderBy[$Key] = $this->elementString($Key[0], $Key[1]).($Value?' ASC':' DESC');
				}
				else
				{
					$OrderBy[$Key] = $this->fieldString($Key).($Value?' ASC':' DESC');
				}
			}

			$OrderBy = array_values($OrderBy);
		}

		if (is_array($GroupBy))
		{
			for ($i = 0, $c = count($GroupBy); $i < $c; ++$i)
			{
				if (is_array($GroupBy[$i]))
				{
					$GroupBy[$i] = $this->elementString($GroupBy[$i][0], $GroupBy[$i][1]);
				}
				else
				{
					$GroupBy[$i] = $this->fieldString($GroupBy[$i]);
				}
			}
		}

		$SQL = 'SELECT '.($Distinct?'DISTINCT ':'').$FieldsPart.' FROM '.$TablesPart.($Where?' WHERE '.$this->elementString(self::ELEMENT_EXPRESSION, $Where):'').($GroupBy?' GROUP BY '.implode(', ', $GroupBy).($Having?' HAVING '.$this->elementString(self::ELEMENT_EXPRESSION, $Having):''):'').($OrderBy?' ORDER BY '.implode(', ', $OrderBy):'').(($Amount || $Offset)?' LIMIT '.(int) $Offset.', '.(int) $Amount:'');

		if ($Mode == self::RETURN_QUERY)
		{
			return $SQL;
		}

		$Statement = $this->PDO->prepare($SQL);
		$Result = ($Statement?$Statement->execute():NULL);

		if ($Result)
		{
			return (($Mode == self::RETURN_RESULT)?$Statement->fetchAll(PDO::FETCH_ASSOC):$Statement);
		}
		else
		{
			return array();
		}
	}

/**
 * Inserts data to database table and returns FALSE if failed, ID of last inserted row or TRUE on success
 * @param string Table
 * @param array Values
 * @param boolean ReturnID
 * @return integer
 */

	public function insertData($Table, $Values, $ReturnID = FALSE)
	{
		$Statement = $this->PDO->prepare('INSERT INTO "'.$this->Prefix.$Table.'" ("'.implode('", "', array_keys($Values)).'") VALUES('.str_repeat('?, ', (count($Values) - 1)).'?)');

		if (!$Statement || !$Statement->execute(array_values($Values)))
		{
			return FALSE;
		}

		if ($ReturnID)
		{
			return $this->PDO->lastinsertid();
		}
		else
		{
			return TRUE;
		}
	}

/**
 * Changes data in database table
 * @param string Table
 * @param array Values
 * @param array Where
 * @return boolean
 */

	public function updateData($Table, $Values, $Where)
	{
		$Parts = array();

		if (!$this->selectAmount($Table, $Where))
		{
			return FALSE;
		}

		foreach ($Values as $Key => $Value)
		{
			if (is_array($Value))
			{
				$Parts[] = '"'.$Key.'" = '.$this->elementString($Value[0], $Value[1]);
			}
			else
			{
				$Parts[] = '"'.$Key.'" = '.$this->PDO->quote($Value);
			}
		}

		$Statement = $this->PDO->prepare('UPDATE "'.$this->Prefix.$Table.'" SET '.implode(', ', $Parts).' WHERE '.$this->elementString(self::ELEMENT_EXPRESSION, $Where));

		return ($Statement?$Statement->execute():FALSE);
	}

/**
 * Deletes data from database table
 * @param string Table
 * @param array Where
 * @return boolean
 */

	public function deleteData($Table, $Where = NULL)
	{
		$Statement = $this->PDO->prepare('DELETE FROM "'.$this->Prefix.$Table.'"'.($Where?' WHERE '.$this->elementString(self::ELEMENT_EXPRESSION, $Where):''));

		return ($Statement?$Statement->execute():FALSE);
	}
}
?>