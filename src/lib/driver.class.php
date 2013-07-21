<?php
/**
 * Database driver class for eStats
 *
 * Filters (for WHERE, HAVING and JOIN ON) example:
 * \code
 * array(EstatsDriver::OPERATOR_GROUPING_START, EstatsDriver::OPERATOR_GROUPING_START, array(EstatsDriver::ELEMENT_OPERATION, array('key', EstatsDriver::OPERATOR_EQUAL, 'test')), EstatsDriver::OPERATOR_OR, array(EstatsDriver::ELEMENT_OPERATION, array('key', EstatsDriver::OPERATOR_ISNULL)), EstatsDriver::OPERATOR_GROUPING_END, EstatsDriver::OPERATOR_AND, array(EstatsDriver::ELEMENT_OPERATION, array('key2', EstatsDriver::OPERATOR_EQUAL, 'testr')), EstatsDriver::OPERATOR_GROUPING_END)
 * \endcode
 *
 * Select $fields parameter example:
 * \code
 * array('key', array(EstatsDriver::ELEMENT_FUNCTION, array(EstatsDriver::FUNCTION_CONCATENATION, array(array(EstatsDriver::ELEMENT_FIELD, 'test'), array(EstatsDriver::ELEMENT_VALUE, ' - '), array(EstatsDriver::ELEMENT_FIELD, 'test2'))), 'alias'))
 * \endcode
 *
 * Update $values parameter example (sets key to current value of key + 1 and key 2 to 'value'):
 * \code
 * array(
 * 	'key' => array(EstatsDriver::ELEMENT_EXPRESSION, array(array(EstatsDriver::ELEMENT_FIELD, 'key'), EstatsDriver::OPERATOR_PLUS, array(EstatsDriver::ELEMENT_VALUE, 1))),
 * 	'key2' => 'value'
 * )
 * \endcode
 *
 * Date and time placeholders used in conjunction with EstatsDriver::FUNCTION_DATETIME:
 * \code%S - seconds: 00-59
 * %M - minute: 0-59
 * %H - hour: 0-23
 * %w - day of week 0-6 with sunday==0
 * %d - day of month: 0-31
 * %j - day of year: 1-366
 * %W - week of year: 0-53
 * %m - month: 1-12
 * %Y - year: 0-9999
 * %% - %\endcode
 *
 * @author Emdek <http://emdek.pl>
 * @version 0.9.05
 */

abstract class EstatsDriver
{

/**
 * Element type key
 */

	const ELEMENT_FIELD = 1;

/**
 * Element type value
 */

	const ELEMENT_VALUE = 2;

/**
 * Element type function
 */

	const ELEMENT_FUNCTION = 4;

/**
 * Element type operation
 */

	const ELEMENT_OPERATION = 8;

/**
 * Element type expression
 */

	const ELEMENT_EXPRESSION = 16;

/**
 * Element type subquery
 */

	const ELEMENT_SUBQUERY = 32;

/**
 * Element type concatenation
 */

	const ELEMENT_CONCATENATION = 64;

/**
 * Element type case expression
 */

	const ELEMENT_CASE = 128;

/**
 * Join type "natural" (can be combined with other joins)
 */

	const JOIN_NATURAL = 1;

/**
 * Join type "cross"
 */

	const JOIN_CROSS = 2;

/**
 * Join type "inner"
 */

	const JOIN_INNER = 4;

/**
 * Join type "left"
 */

	const JOIN_LEFT = 8;

/**
 * Join type "right"
 */

	const JOIN_RIGHT = 16;

/**
 * Join type "full"
 */

	const JOIN_FULL = 32;

/**
 * The "not" operator (can be combined with other operators)
 */

	const OPERATOR_NOT = 1;

/**
 * The "and" operator
 */

	const OPERATOR_AND = 2;

/**
 * The "or" operator
 */

	const OPERATOR_OR = 4;

/**
 * The "equal" operator
 */

	const OPERATOR_EQUAL = 8;

/**
 * The "is null" operator
 */

	const OPERATOR_ISNULL = 16;

/**
 * The "regexp" operator
 */

	const OPERATOR_REGEXP = 32;

/**
 * The "like" operator
 */

	const OPERATOR_LIKE = 64;

/**
 * The "greater than" operator
 */

	const OPERATOR_GREATER = 128;

/**
 * The "greater than or equal" operator
 */

	const OPERATOR_GREATEROREQUAL = 256;

/**
 * The "less than" operator
 */

	const OPERATOR_LESS = 1024;

/**
 * The "less than or equal" operator
 */

	const OPERATOR_LESSOREQUAL = 2048;

/**
 * The "between" operator
 */

	const OPERATOR_BETWEEN = 4096;

/**
 * The "in" operator
 */

	const OPERATOR_IN = 8192;

/**
 * The "plus" operator
 */

	const OPERATOR_PLUS = 16384;

/**
 * The "minus" operator
 */

	const OPERATOR_MINUS = 32768;

/**
 * The "increase" operator
 */

	const OPERATOR_INCREASE = 65536;

/**
 * The "decrease" operator
 */

	const OPERATOR_DECREASE = 131072;

/**
 * The "multiplication" operator
 */

	const OPERATOR_MULTIPLICATION = 262144;

/**
 * The "division" operator
 */

	const OPERATOR_DIVISION = 524288;

/**
 * The clause grouping start operator
 */

	const OPERATOR_GROUPING_START = 1048576;

/**
 * The clause grouping end operator
 */

	const OPERATOR_GROUPING_END = 2097152;

/**
 * The join on operator
 */

	const OPERATOR_JOIN_ON = 4194304;

/**
 * The join using operator
 */

	const OPERATOR_JOIN_USING = 8388608;

/**
 * The "count" function
 */

	const FUNCTION_COUNT = 1;

/**
 * The "date and time" function
 */

	const FUNCTION_DATETIME = 2;

/**
 * The "sum" function
 */

	const FUNCTION_SUM = 4;

/**
 * The "minimum" function
 */

	const FUNCTION_MIN = 8;

/**
 * The "maximum" function
 */

	const FUNCTION_MAX = 16;

/**
 * The "average" function
 */

	const FUNCTION_AVG = 32;

/**
 * The "UNIX time stamp" function
 */

	const FUNCTION_TIMESTAMP = 64;

/**
 * Return query result
 */

	const RETURN_RESULT = 1;

/**
 * Return results as object
 */

	const RETURN_OBJECT = 2;

/**
 * Return native query
 */

	const RETURN_QUERY = 4;

/**
 * Contains reference to PDO object
 */

	protected $connection = NULL;

/**
 * Contains optional tables prefix
 */

	protected $prefix = '';

/**
 * Contains true if connection is active
 */

	protected $connected = FALSE;

/**
 * Contains driver information
 */

	protected $information = array();

/**
 * Returns TRUE if driver is available
 * @return boolean
 */

	abstract public function isAvailable();

/**
 * Generates connection string
 * @param array Parameters
 * @return string
 */

	abstract public function connectionString($parameters);

/**
 * Returns option value
 * @param string Option
 * @return string
 */

	abstract public function option($option);

/**
 * Connects to the database
 * @param string Connection
 * @param string User
 * @param string Password
 * @param string Prefix
 * @param boolean Persistent
 * @return boolean
 */

	public function connect($connection, $user, $password, $prefix = '', $persistent = FALSE)
	{
		try
		{
			$this->connection = new PDO($connection, $user, $password, ($persistent?array(PDO::ATTR_PERSISTENT => TRUE):array()));
			$this->connected = TRUE;
		}
		catch (Exception $e)
		{
			$this->connected = FALSE;
		}

		$this->prefix = $prefix;

		return $this->connected;
	}


/**
 * Disconnects from the database
*/
	public function disconnect()
	{
		$this->connection = NULL;
		$this->connected = FALSE;
	}

/**
 * Creates database table
 * @param string Table
 * @param array Atrributes
 * @param boolean Replace
 * @return boolean
 */

	abstract public function createTable($table, $attributes, $replace = FALSE);

/**
 * Deletes database table
 * @param string Table
 * @return boolean
 */

	abstract public function deleteTable($table);

/**
 * Checks if database table exists
 * @param string Table
 * @return boolean
 */

	abstract public function tableExists($table);

/**
 * Returns database table size in bytes or FALSE if failed
 * @param string Table
 * @return integer
 */

	abstract public function tableSize($table);

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

	abstract public function selectData($tables, $fields = NULL, $where = NULL, $amount = 0, $offset = 0, $orderBy = NULL, $groupBy = NULL, $having = NULL, $distinct = FALSE, $mode = self::RETURN_RESULT);

/**
 * Returns value of single field from database table
 * @param string Table
 * @param string Field
 * @param array Where
 * @param array OrderBy
 * @param integer Offset
 * @return string
 */

	public function selectField($table, $field, $where = NULL, $orderBy = NULL, $offset = 0)
	{
		$data = $this->selectData(array($table), array($field), $where, 1, $offset, $orderBy);

		return ($data ? array_shift($data[0]) : '');
	}

/**
 * Returns single column from database table
 * @param string Table
 * @param mixed Field
 * @param array Where
 * @param integer Amount
 * @param integer Offset
 * @param array OrderBy
 * @param array GroupBy
 * @param array Having
 * @param boolean Distinct
 * @return array
 */

	public function selectColumn($table, $field, $where = NULL, $amount = 0, $offset = 0, $orderBy = NULL, $groupBy = NULL, $having = NULL, $distinct = FALSE)
	{
		$data = $this->selectData(array($table), array($field), $where, $amount, $offset, $orderBy, $groupBy, $having, $distinct);
		$column = array();

		for ($i = 0, $c = count($data); $i < $c; ++$i)
		{
			$column[] = array_shift($data[$i]);
		}

		return $column;
	}

/**
 * Returns single row from database table
 * @param string Table
 * @param array Fields
 * @param array Where
 * @param integer Offset
 * @param array OrderBy
 * @param array GroupBy
 * @param array Having
 * @param boolean Distinct
 * @return array
 */

	public function selectRow($table, $fields = NULL, $where = NULL, $offset = 0, $orderBy = NULL, $groupBy = NULL, $having = NULL, $distinct = FALSE)
	{
		$data = $this->selectData(array($table), $fields, $where, 1, $offset, $orderBy, $groupBy, $having, $distinct);

		return ($data ? $data[0] : array());
	}

/**
 * Returns amount of data rows in database table or FALSE if failed
 * @param string Table
 * @param array Where
 * @param array GroupBy
 * @param array Having
 * @param boolean Distinct
 * @return array
 */

	public function selectAmount($table, $where = NULL, $groupBy = NULL, $having = NULL, $distinct = FALSE)
	{
		$data = $this->selectData(array($table), array(self::FUNCTION_COUNT), $where, 0, 0, NULL, $groupBy, $having, $distinct);

		return ($data ? array_shift($data[0]) : FALSE);
	}

/**
 * Inserts data to database table and returns FALSE if failed, ID of last inserted row or TRUE on success
 * @param string Table
 * @param array Values
 * @param boolean ReturnID
 * @return integer
 */

	abstract public function insertData($table, $values, $returnID = FALSE);

/**
 * Changes data in database table
 * @param string Table
 * @param array Values
 * @param array Where
 * @return boolean
 */

	abstract public function updateData($table, $values, $where);

/**
 * Deletes data from database table
 * @param string Table
 * @param array Where
 * @return boolean
 */

	abstract public function deleteData($table, $where = NULL);

/**
 * Initiates transaction
 */

	public function beginTransaction()
	{
		$this->connection->beginTransaction();
	}

/**
 * Commits transaction
 */

	public function commitTransaction()
	{
		$this->connection->commit();
	}

/**
 * Rolls back transaction
 */

	public function rollBackTransaction()
	{
		$this->connection->rollBack();
	}
}
?>