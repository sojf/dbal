<?php
namespace Sojf\DBAL;


use Sojf\DBAL\Exceptions\DBAL as DBALException;
use Sojf\DBAL\Exceptions\Connection as ConnectionException;

use Sojf\DBAL\Query\QueryBuilder;

use Sojf\DBAL\Abstracts\Platform;
use Sojf\DBAL\Abstracts\Type;

use Sojf\DBAL\Interfaces\PingableConnection;
use Sojf\DBAL\Interfaces\Connection as ConnectionInterface;
use Sojf\DBAL\Interfaces\Driver;
use Sojf\DBAL\Interfaces\VersionAwarePlatformDriver;
use Sojf\DBAL\Interfaces\ServerInfoAwareConnection;

class Connection implements ConnectionInterface
{
    /**
     * Constant for transaction isolation level READ UNCOMMITTED.
     */
    const TRANSACTION_READ_UNCOMMITTED = 1;

    /**
     * Constant for transaction isolation level READ COMMITTED.
     */
    const TRANSACTION_READ_COMMITTED = 2;

    /**
     * Constant for transaction isolation level REPEATABLE READ.
     */
    const TRANSACTION_REPEATABLE_READ = 3;

    /**
     * Constant for transaction isolation level SERIALIZABLE.
     */
    const TRANSACTION_SERIALIZABLE = 4;

    /**
     * Represents an array of ints to be expanded by Doctrine SQL parsing.
     *
     * @var integer
     */
    const PARAM_INT_ARRAY = 101;

    /**
     * Represents an array of strings to be expanded by Doctrine SQL parsing.
     *
     * @var integer
     */
    const PARAM_STR_ARRAY = 102;

    /**
     * Offset by which PARAM_* constants are detected as arrays of the param type.
     *
     * @var integer
     */
    const ARRAY_PARAM_OFFSET = 100;

    /**
     * The wrapped driver connection.
     *
     * @var \Sojf\DBAL\Interfaces\Connection
     */
    protected $_conn;

    /**
     * @var \Sojf\DBAL\Query\Expression\ExpressionBuilder
     */
    protected $_expr;

    /**
     * Whether or not a connection has been established.
     *
     * @var boolean
     */
    private $_isConnected = false;

    /**
     * The current auto-commit mode of this connection.
     *
     * @var boolean
     */
    private $autoCommit = true;

    /**
     * The transaction nesting level.
     *
     * @var integer
     */
    private $_transactionNestingLevel = 0;

    /**
     * The currently active transaction isolation level.
     *
     * @var integer
     */
    private $_transactionIsolationLevel;

    /**
     * If nested transactions should use savepoints.
     *
     * @var boolean
     */
    private $_nestTransactionsWithSavepoints = false;

    /**
     * The parameters used during creation of the Connection instance.
     *
     * @var array
     */
    private $_params = array();

    /**
     * The DatabasePlatform object that provides information about the
     * database platform used by the connection.
     *
     * @var \Sojf\DBAL\Abstracts\Platform
     */
    private $platform;

    /**
     * The schema manager.
     *
     * @var \Sojf\DBAL\Abstracts\SchemaManager
     */
    protected $_schemaManager;

    /**
     * The used DBAL driver.
     *
     * @var \Sojf\DBAL\Interfaces\Driver
     */
    protected $_driver;

    /**
     * Flag that indicates whether the current transaction is marked for rollback only.
     *
     * @var boolean
     */
    private $_isRollbackOnly = false;

    /**
     * @var integer
     */
    protected $defaultFetchMode = \PDO::FETCH_ASSOC;


    public function __construct(array $params = null, Driver $driver = null)
    {
        if (isset($driver)) {

            $this->_driver = $driver;
        }

        if (isset($params)) {

            $this->_params = $params;
        }
        
        if (isset($params['pdo'])) {

            $this->_conn = $params['pdo'];

            $this->_isConnected = true;

            unset($this->_params['pdo']);
        }

        $this->_expr = new Query\Expression\ExpressionBuilder($this);
    }

    /**
     * Gets the parameters used during instantiation.
     *
     * @return array
     */
    public function getParams()
    {
        return $this->_params;
    }

    /**
     * Gets the name of the database this Connection is connected to.
     *
     * @return string
     */
    public function getDatabase()
    {
        return $this->_driver->getDatabase($this);
    }

    /**
     * Gets the hostname of the currently connected database.
     *
     * @return string|null
     */
    public function getHost()
    {
        return isset($this->_params['host']) ? $this->_params['host'] : null;
    }

    /**
     * Gets the port of the currently connected database.
     *
     * @return mixed
     */
    public function getPort()
    {
        return isset($this->_params['port']) ? $this->_params['port'] : null;
    }

    /**
     * Gets the username used by this connection.
     *
     * @return string|null
     */
    public function getUsername()
    {
        return isset($this->_params['user']) ? $this->_params['user'] : null;
    }

    /**
     * Gets the password used by this connection.
     *
     * @return string|null
     */
    public function getPassword()
    {
        return isset($this->_params['password']) ? $this->_params['password'] : null;
    }

    /**
     * Gets the DBAL driver instance.
     *
     * @return \Sojf\DBAL\Interfaces\Driver
     */
    public function getDriver()
    {
        return $this->_driver;
    }
    
    /**
     * Gets the DatabasePlatform for the connection.
     *
     * @return \Sojf\DBAL\Abstracts\Platform
     */
    public function getDatabasePlatform()
    {
        if (null == $this->platform) {
            
            $this->detectDatabasePlatform();
        }

        return $this->platform;
    }

    /**
     * Gets the ExpressionBuilder for the connection.
     *
     * @return \Sojf\DBAL\Query\Expression\ExpressionBuilder
     */
    public function getExpressionBuilder()
    {
        return $this->_expr;
    }

    /**
     * Establishes the connection with the database.
     *
     * @return boolean TRUE if the connection was successfully established, FALSE if
     *                 the connection is already open.
     */ 
    public function connect()
    {
        // todo: 调用driver的connect方法
        
        if ($this->_isConnected) return false;

        $driverOptions = isset($this->_params['driverOptions']) ? $this->_params['driverOptions'] : array();
        
        $user = isset($this->_params['user']) ? $this->_params['user'] : null;
        
        $password = isset($this->_params['password']) ? $this->_params['password'] : null;

        $this->_conn = $this->_driver->connect($this->_params, $user, $password, $driverOptions);
        
        $this->_isConnected = true;

        if (null === $this->platform) {
            
            $this->detectDatabasePlatform();
        }

        if (false === $this->autoCommit) {
            
            $this->beginTransaction();
        }

        return true;
    }

    /**
     * Detects and sets the database platform.
     *
     * Evaluates custom platform class and version in order to set the correct platform.
     *
     * @throws DBALException if an invalid platform was specified for this connection.
     */
    private function detectDatabasePlatform()
    {
        if ( ! isset($this->_params['platform'])) {
            
            $version = $this->getDatabasePlatformVersion();

            if (null !== $version) {

                $this->platform = $this->_driver->createDatabasePlatformForVersion($version);
            } else {

                $this->platform = $this->_driver->getDatabasePlatform();
            }

        } elseif ($this->_params['platform'] instanceof Platform) {

            $this->platform = $this->_params['platform'];
        } else {

            throw DBALException::invalidPlatformSpecified();
        }
    }

    /**
     * Returns the version of the related platform if applicable.
     *
     * Returns null if either the driver is not capable to create version
     * specific platform instances, no explicit server version was specified
     * or the underlying driver connection cannot determine the platform
     * version without having to query it (performance reasons).
     *
     * @return string|null
     */
    private function getDatabasePlatformVersion()
    {
        // Driver does not support version specific platforms.
//        if ( ! $this->_driver instanceof VersionAwarePlatformDriver) {
//
//            return null;
//        }

        // Explicit platform version requested (supersedes auto-detection).
        if (isset($this->_params['serverVersion'])) {

            return $this->_params['serverVersion'];
        }

        // If not connected, we need to connect now to determine the platform version.
        if (null === $this->_conn) {
            $this->connect();
        }

        // Automatic platform version detection.
//        if ( $this->_conn instanceof ServerInfoAwareConnection && !$this->_conn->requiresQueryForServerVersion() ) {
//        }
        return $this->_conn->getServerVersion();

        // Unable to detect platform version.
//        return null;
    }

    /**
     * Returns the current auto-commit mode for this connection.
     *
     * @return boolean True if auto-commit mode is currently enabled for this connection, false otherwise.
     *
     * @see    setAutoCommit
     */
    public function isAutoCommit()
    {
        return true === $this->autoCommit;
    }

    /**
     * Sets auto-commit mode for this connection.
     *
     * If a connection is in auto-commit mode, then all its SQL statements will be executed and committed as individual
     * transactions. Otherwise, its SQL statements are grouped into transactions that are terminated by a call to either
     * the method commit or the method rollback. By default, new connections are in auto-commit mode.
     *
     * NOTE: If this method is called during a transaction and the auto-commit mode is changed, the transaction is
     * committed. If this method is called and the auto-commit mode is not changed, the call is a no-op.
     *
     * @param boolean $autoCommit True to enable auto-commit mode; false to disable it.
     *
     * @see   isAutoCommit
     */
    public function setAutoCommit($autoCommit)
    {
        $autoCommit = (boolean) $autoCommit;

        // Mode not changed, no-op.
        if ($autoCommit === $this->autoCommit) {
            return;
        }

        $this->autoCommit = $autoCommit;

        // Commit all currently active transactions if any when switching auto-commit mode.
        if (true === $this->_isConnected && 0 !== $this->_transactionNestingLevel) {

            $this->commitAll();
        }
    }

    /**
     * Sets the fetch mode.
     *
     * @param integer $fetchMode
     *
     * @return void
     */
    public function setFetchMode($fetchMode)
    {
        $this->defaultFetchMode = $fetchMode;
    }

    /**
     * Prepares and executes an SQL query and returns the first row of the result
     * as an associative array.
     *
     * @param string $statement The SQL query.
     * @param array  $params    The query parameters.
     * @param array  $types     The query parameter types.
     *
     * @return array
     */
    public function fetchAssoc($statement, array $params = array(), array $types = array())
    {
        return $this->executeQuery($statement, $params, $types)->fetch(\PDO::FETCH_ASSOC);
    }

    /**
     * Prepares and executes an SQL query and returns the first row of the result
     * as a numerically indexed array.
     *
     * @param string $statement The SQL query to be executed.
     * @param array  $params    The prepared statement params.
     * @param array  $types     The query parameter types.
     *
     * @return array
     */
    public function fetchArray($statement, array $params = array(), array $types = array())
    {
        return $this->executeQuery($statement, $params, $types)->fetch(\PDO::FETCH_NUM);
    }

    /**
     * Prepares and executes an SQL query and returns the value of a single column
     * of the first row of the result.
     *
     * @param string  $statement The SQL query to be executed.
     * @param array   $params    The prepared statement params.
     * @param integer $column    The 0-indexed column number to retrieve.
     * @param array  $types      The query parameter types.
     *
     * @return mixed
     */
    public function fetchColumn($statement, array $params = array(), $column = 0, array $types = array())
    {
        return $this->executeQuery($statement, $params, $types)->fetchColumn($column);
    }

    /**
     * Whether an actual connection to the database is established.
     *
     * @return boolean
     */
    public function isConnected()
    {
        return $this->_isConnected;
    }

    /**
     * Checks whether a transaction is currently active.
     *
     * @return boolean TRUE if a transaction is currently active, FALSE otherwise.
     */
    public function isTransactionActive()
    {
        return $this->_transactionNestingLevel > 0;
    }

    /**
     * Executes an SQL DELETE statement on a table.
     *
     * Table expression and columns are not escaped and are not safe for user-input.
     *
     * @param string $tableExpression  The expression of the table on which to delete.
     * @param array  $identifier The deletion criteria. An associative array containing column-value pairs.
     * @param array  $types      The types of identifiers.
     *
     * @return integer The number of affected rows.
     * @throws \Exception
     */
    public function delete($tableExpression, array $identifier, array $types = array())
    {
        if (empty($identifier)) {

            throw new \Exception('Empty criteria was used, expected non-empty criteria');
        }

        $this->connect();

        $criteria = array();

        foreach (array_keys($identifier) as $columnName) {
            $criteria[] = $columnName . ' = ?';
        }

        return $this->executeUpdate(
            'DELETE FROM ' . $tableExpression . ' WHERE ' . implode(' AND ', $criteria),
            array_values($identifier),
            is_string(key($types)) ? $this->extractTypeValues($identifier, $types) : $types
        );
    }

    /**
     * Closes the connection.
     *
     * @return void
     */
    public function close()
    {
        $this->_conn = null;

        $this->_isConnected = false;
    }

    /**
     * Sets the transaction isolation level.
     *
     * @param integer $level The level to set.
     *
     * @return integer
     */
    public function setTransactionIsolation($level)
    {
        $this->_transactionIsolationLevel = $level;

        return $this->executeUpdate($this->getDatabasePlatform()->getSetTransactionIsolationSQL($level));
    }

    /**
     * Gets the currently active transaction isolation level.
     *
     * @return integer The current transaction isolation level.
     */
    public function getTransactionIsolation()
    {
        if (null === $this->_transactionIsolationLevel) {
            $this->_transactionIsolationLevel = $this->getDatabasePlatform()->getDefaultTransactionIsolationLevel();
        }

        return $this->_transactionIsolationLevel;
    }

    /**
     * Executes an SQL UPDATE statement on a table.
     *
     * Table expression and columns are not escaped and are not safe for user-input.
     *
     * @param string $tableExpression  The expression of the table to update quoted or unquoted.
     * @param array  $data       An associative array containing column-value pairs.
     * @param array  $identifier The update criteria. An associative array containing column-value pairs.
     * @param array  $types      Types of the merged $data and $identifier arrays in that order.
     *
     * @return integer The number of affected rows.
     */
    public function update($tableExpression, array $data, array $identifier, array $types = array())
    {
        $this->connect();
        $set = array();

        foreach ($data as $columnName => $value) {
            $set[] = $columnName . ' = ?';
        }

        if (is_string(key($types))) {
            $types = $this->extractTypeValues(array_merge($data, $identifier), $types);
        }

        $params = array_merge(array_values($data), array_values($identifier));

        $sql  = 'UPDATE ' . $tableExpression . ' SET ' . implode(', ', $set)
            . ' WHERE ' . implode(' = ? AND ', array_keys($identifier))
            . ' = ?';

        return $this->executeUpdate($sql, $params, $types);
    }

    /**
     * Inserts a table row with specified data.
     *
     * Table expression and columns are not escaped and are not safe for user-input.
     *
     * @param string $tableExpression The expression of the table to insert data into, quoted or unquoted.
     * @param array  $data      An associative array containing column-value pairs.
     * @param array  $types     Types of the inserted data.
     *
     * @return integer The number of affected rows.
     */
    public function insert($tableExpression, array $data, array $types = array())
    {
        $this->connect();

        if (empty($data)) {
            return $this->executeUpdate('INSERT INTO ' . $tableExpression . ' ()' . ' VALUES ()');
        }

        return $this->executeUpdate(
            'INSERT INTO ' . $tableExpression . ' (' . implode(', ', array_keys($data)) . ')' .
            ' VALUES (' . implode(', ', array_fill(0, count($data), '?')) . ')',
            array_values($data),
            is_string(key($types)) ? $this->extractTypeValues($data, $types) : $types
        );
    }

    /**
     * Extract ordered type list from two associate key lists of data and types.
     *
     * @param array $data
     * @param array $types
     *
     * @return array
     */
    private function extractTypeValues(array $data, array $types)
    {
        $typeValues = array();

        foreach ($data as $k => $_) {
            $typeValues[] = isset($types[$k])
                ? $types[$k]
                : \PDO::PARAM_STR;
        }

        return $typeValues;
    }

    /**
     * Quotes a string so it can be safely used as a table or column name, even if
     * it is a reserved name.
     *
     * Delimiting style depends on the underlying database platform that is being used.
     *
     * NOTE: Just because you CAN use quoted identifiers does not mean
     * you SHOULD use them. In general, they end up causing way more
     * problems than they solve.
     *
     * @param string $str The name to be quoted.
     *
     * @return string The quoted name.
     */
    public function quoteIdentifier($str)
    {
        return $this->getDatabasePlatform()->quoteIdentifier($str);
    }

    /**
     * Quotes a given input parameter.
     *
     * @param mixed       $input The parameter to be quoted.
     * @param string|null $type  The type of the parameter.
     *
     * @return string The quoted parameter.
     */
    public function quote($input, $type = null)
    {
        $this->connect();

        list($value, $bindingType) = $this->getBindingInfo($input, $type);
        return $this->_conn->quote($value, $bindingType);
    }

    /**
     * Prepares and executes an SQL query and returns the result as an associative array.
     *
     * @param string $sql    The SQL query.
     * @param array  $params The query parameters.
     * @param array  $types  The query parameter types.
     *
     * @return array
     */
    public function fetchAll($sql, array $params = array(), $types = array())
    {
        return $this->executeQuery($sql, $params, $types)->fetchAll();
    }

    /**
     * Prepares an SQL statement.
     *
     * @param string $statement The SQL statement to prepare.
     *
     * @return \Sojf\DBAL\Statement The prepared statement.
     *
     * @throws DBALException
     */
    public function prepare($statement)
    {
        $this->connect();

        try {

            $stmt = new Statement($statement, $this);

        } catch (\Exception $ex) {

            throw DBALException::driverExceptionDuringQuery($this->_driver, $ex, $statement);
        }

        $stmt->setFetchMode($this->defaultFetchMode);

        return $stmt;
    }

    public function executeQuery($query, array $params = array(), $types = array())
    {
        $this->connect();

        try {
            if ($params) {
                list($query, $params, $types) = SQLParserUtils::expandListParameters($query, $params, $types);

                $stmt = $this->_conn->prepare($query);
                if ($types) {
                    $this->_bindTypedValues($stmt, $params, $types);
                    $stmt->execute();
                } else {
                    $stmt->execute($params);
                }
            } else {
                $stmt = $this->_conn->query($query);
            }
        } catch (\Exception $ex) {
            throw DBALException::driverExceptionDuringQuery($this->_driver, $ex, $query, $this->resolveParams($params, $types));
        }

        $stmt->setFetchMode($this->defaultFetchMode);
        
        return $stmt;
    }
    
    /**
     * Executes an, optionally parametrized, SQL query and returns the result,
     * applying a given projection/transformation function on each row of the result.
     *
     * @param string   $query    The SQL query to execute.
     * @param array    $params   The parameters, if any.
     * @param \Closure $function The transformation function that is applied on each row.
     *                           The function receives a single parameter, an array, that
     *                           represents a row of the result set.
     *
     * @return array The projected result of the query.
     */
    public function project($query, array $params, \Closure $function)
    {
        $result = array();
        $stmt = $this->executeQuery($query, $params);

        while ($row = $stmt->fetch()) {
            $result[] = $function($row);
        }

        $stmt->closeCursor();

        return $result;
    }

    /**
     * Executes an SQL statement, returning a result set as a Statement object.
     *
     * @return \Sojf\DBAL\Interfaces\Statement
     *
     * @throws DBALException
     */
    public function query()
    {
        $this->connect();

        $args = func_get_args();
        
        try {
            switch (func_num_args()) {
                case 1:
                    $statement = $this->_conn->query($args[0]);
                    break;
                case 2:
                    $statement = $this->_conn->query($args[0], $args[1]);
                    break;
                default:
                    $statement = call_user_func_array(array($this->_conn, 'query'), $args);
                    break;
            }
        } catch (\Exception $ex) {

            throw DBALException::driverExceptionDuringQuery($this->_driver, $ex, $args[0]);
        }

        $statement->setFetchMode($this->defaultFetchMode);

        return $statement;
    }

    /**
     * Executes an SQL INSERT/UPDATE/DELETE query with the given parameters
     * and returns the number of affected rows.
     *
     * This method supports PDO binding types as well as DBAL mapping types.
     *
     * @param string $query  The SQL query.
     * @param array  $params The query parameters.
     * @param array  $types  The parameter types.
     *
     * @return integer The number of affected rows.
     *
     * @throws DBALException
     */
    public function executeUpdate($query, array $params = array(), array $types = array())
    {
        $this->connect();

        try {
            if ($params) {

                list($query, $params, $types) = SQLParserUtils::expandListParameters($query, $params, $types);

                $stmt = $this->_conn->prepare($query);
                if ($types) {

                    $this->_bindTypedValues($stmt, $params, $types);
                    $stmt->execute();
                } else {

                    $stmt->execute($params);
                }

                $result = $stmt->rowCount();
            } else {

                $result = $this->_conn->exec($query);
            }
        } catch (\Exception $ex) {

            throw DBALException::driverExceptionDuringQuery($this->_driver, $ex, $query, $this->resolveParams($params, $types));
        }

        return $result;
    }

    /**
     * Executes an SQL statement and return the number of affected rows.
     *
     * @param string $statement
     *
     * @return integer The number of affected rows.
     *
     * @throws DBALException
     */
    public function exec($statement)
    {
        $this->connect();
        
        try {
            
            $result = $this->_conn->exec($statement);
        } catch (\Exception $ex) {
            
            throw DBALException::driverExceptionDuringQuery($this->_driver, $ex, $statement);
        }

        return $result;
    }

    /**
     * Returns the current transaction nesting level.
     *
     * @return integer The nesting level. A value of 0 means there's no active transaction.
     */
    public function getTransactionNestingLevel()
    {
        return $this->_transactionNestingLevel;
    }

    /**
     * Fetches the SQLSTATE associated with the last database operation.
     *
     * @return integer The last error code.
     */
    public function errorCode()
    {
        $this->connect();

        return $this->_conn->errorCode();
    }

    /**
     * Fetches extended error information associated with the last database operation.
     *
     * @return array The last error information.
     */
    public function errorInfo()
    {
        $this->connect();

        return $this->_conn->errorInfo();
    }

    /**
     * Returns the ID of the last inserted row, or the last value from a sequence object,
     * depending on the underlying driver.
     *
     * Note: This method may not return a meaningful or consistent result across different drivers,
     * because the underlying database may not even support the notion of AUTO_INCREMENT/IDENTITY
     * columns or sequences.
     *
     * @param string|null $seqName Name of the sequence object from which the ID should be returned.
     *
     * @return string A string representation of the last inserted ID.
     */
    public function lastInsertId($seqName = null)
    {
        $this->connect();

        return $this->_conn->lastInsertId($seqName);
    }

    /**
     * Executes a function in a transaction.
     *
     * The function gets passed this Connection instance as an (optional) parameter.
     *
     * If an exception occurs during execution of the function or transaction commit,
     * the transaction is rolled back and the exception re-thrown.
     *
     * @param \Closure $func The function to execute transactionally.
     *
     * @return void
     *
     * @throws \Exception
     */
    public function transactional(\Closure $func)
    {
        $this->beginTransaction();

        try {

            $func($this);
            $this->commit();

        } catch (\Exception $e) {

            $this->rollBack();
            throw $e;
        }
    }

    /**
     * Sets if nested transactions should use savepoints.
     *
     * @param boolean $nestTransactionsWithSavepoints
     *
     * @return void
     *
     * @throws \Sojf\DBAL\Exceptions\Connection
     */
    public function setNestTransactionsWithSavepoints($nestTransactionsWithSavepoints)
    {
        if ($this->_transactionNestingLevel > 0) {
            throw ConnectionException::mayNotAlterNestedTransactionWithSavepointsInTransaction();
        }

        if ( ! $this->getDatabasePlatform()->supportsSavepoints()) {
            throw ConnectionException::savepointsNotSupported();
        }

        $this->_nestTransactionsWithSavepoints = (bool) $nestTransactionsWithSavepoints;
    }

    /**
     * Gets if nested transactions should use savepoints.
     *
     * @return boolean
     */
    public function getNestTransactionsWithSavepoints()
    {
        return $this->_nestTransactionsWithSavepoints;
    }

    /**
     * Returns the savepoint name to use for nested transactions are false if they are not supported
     * "savepointFormat" parameter is not set
     *
     * @return mixed A string with the savepoint name or false.
     */
    protected function _getNestedTransactionSavePointName()
    {
        return 'DOCTRINE2_SAVEPOINT_'.$this->_transactionNestingLevel;
    }

    /**
     * Starts a transaction by suspending auto-commit mode.
     *
     * @return void
     */
    public function beginTransaction()
    {
        $this->connect();

        ++$this->_transactionNestingLevel;

        $this->_conn->beginTransaction();
    }

    /**
     * Commits the current transaction.
     *
     * @return void
     *
     * @throws \Sojf\DBAL\Exceptions\Connection If the commit failed due to no active transaction or
     *                                            because the transaction was marked for rollback only.
     */
    public function commit()
    {
        if ($this->_transactionNestingLevel == 0) {
            
            throw ConnectionException::noActiveTransaction();
        }

        if ($this->_isRollbackOnly) {
            
            throw ConnectionException::commitFailedRollbackOnly();
        }

        $this->connect();

        if ($this->_transactionNestingLevel == 1) {

            $this->_conn->commit();

        } elseif ($this->_nestTransactionsWithSavepoints) {

            $this->releaseSavepoint($this->_getNestedTransactionSavePointName());
        }

        --$this->_transactionNestingLevel;

        if (false === $this->autoCommit && 0 === $this->_transactionNestingLevel) {

            $this->beginTransaction();
        }
    }

    /**
     * Commits all current nesting transactions.
     */
    private function commitAll()
    {
        while (0 !== $this->_transactionNestingLevel) {
            if (false === $this->autoCommit && 1 === $this->_transactionNestingLevel) {
                // When in no auto-commit mode, the last nesting commit immediately starts a new transaction.
                // Therefore we need to do the final commit here and then leave to avoid an infinite loop.
                $this->commit();

                return;
            }

            $this->commit();
        }
    }

    /**
     * Cancels any database changes done during the current transaction.
     *
     * This method can be listened with onPreTransactionRollback and onTransactionRollback
     * eventlistener methods.
     *
     * @throws \Sojf\DBAL\Exceptions\Connection If the rollback operation failed.
     */
    public function rollBack()
    {
        if ($this->_transactionNestingLevel == 0) {

            ConnectionException::noActiveTransaction();
        }

        $this->connect();

        if ($this->_transactionNestingLevel == 1) {

            $this->_transactionNestingLevel = 0;

            $this->_conn->rollBack();

            $this->_isRollbackOnly = false;

            if (false === $this->autoCommit) {

                $this->beginTransaction();
            }
            
        } elseif ($this->_nestTransactionsWithSavepoints) {

            $this->rollbackSavepoint($this->_getNestedTransactionSavePointName());
            --$this->_transactionNestingLevel;

        } else {

            $this->_isRollbackOnly = true;
            --$this->_transactionNestingLevel;
        }
    }

    /**
     * Creates a new savepoint.
     *
     * @param string $savepoint The name of the savepoint to create.
     *
     * @return void
     *
     * @throws \Sojf\DBAL\Exceptions\Connection
     */
    public function createSavepoint($savepoint)
    {
        if ( ! $this->getDatabasePlatform()->supportsSavepoints()) {

            throw new \Exception('Savepoints are not supported by this driver.');
        }

        $this->_conn->exec($this->platform->createSavePoint($savepoint));
    }

    /**
     * Releases the given savepoint.
     *
     * @param string $savepoint The name of the savepoint to release.
     *
     * @return void
     *
     * @throws \Sojf\DBAL\Exceptions\Connection
     */
    public function releaseSavepoint($savepoint)
    {
        if ( ! $this->getDatabasePlatform()->supportsSavepoints()) {

            ConnectionException::savepointsNotSupported();
        }

        if ($this->platform->supportsReleaseSavepoints()) {
            $this->_conn->exec($this->platform->releaseSavePoint($savepoint));
        }
    }

    /**
     * Rolls back to the given savepoint.
     *
     * @param string $savepoint The name of the savepoint to rollback to.
     *
     * @return void
     *
     * @throws \Sojf\DBAL\Exceptions\Connection
     */
    public function rollbackSavepoint($savepoint)
    {
        if ( ! $this->getDatabasePlatform()->supportsSavepoints()) {

            ConnectionException::savepointsNotSupported();
        }

        $this->_conn->exec($this->platform->rollbackSavePoint($savepoint));
    }

    /**
     * Gets the wrapped driver connection.
     *
     * @return \Sojf\DBAL\Interfaces\Connection
     */
    public function getWrappedConnection()
    {
        $this->connect();

        return $this->_conn;
    }

    /**
     * Gets the SchemaManager that can be used to inspect or change the
     * database schema through the connection.
     *
     * @return \Sojf\DBAL\Abstracts\SchemaManager
     */
    public function getSchemaManager()
    {
        if ( ! $this->_schemaManager) {

            $this->_schemaManager = $this->_driver->getSchemaManager($this);
        }

        return $this->_schemaManager;
    }

    /**
     * Marks the current transaction so that the only possible
     * outcome for the transaction to be rolled back.
     *
     * @return void
     *
     * @throws \Sojf\DBAL\Exceptions\Connection If no transaction is active.
     */
    public function setRollbackOnly()
    {
        if ($this->_transactionNestingLevel == 0) {

            ConnectionException::noActiveTransaction();
        }
        $this->_isRollbackOnly = true;
    }

    /**
     * Checks whether the current transaction is marked for rollback only.
     *
     * @return boolean
     *
     * @throws \Sojf\DBAL\Exceptions\Connection If no transaction is active.
     */
    public function isRollbackOnly()
    {
        if ($this->_transactionNestingLevel == 0) {

            ConnectionException::noActiveTransaction();
        }

        return $this->_isRollbackOnly;
    }

    /**
     * Converts a given value to its database representation according to the conversion
     * rules of a specific DBAL mapping type.
     *
     * @param mixed  $value The value to convert.
     * @param string $type  The name of the DBAL mapping type.
     *
     * @return mixed The converted value.
     */
    public function convertToDatabaseValue($value, $type)
    {
        return Type::getType($type)->convertToDatabaseValue($value, $this->getDatabasePlatform());
    }

    /**
     * Converts a given value to its PHP representation according to the conversion
     * rules of a specific DBAL mapping type.
     *
     * @param mixed  $value The value to convert.
     * @param string $type  The name of the DBAL mapping type.
     *
     * @return mixed The converted type.
     */
    public function convertToPHPValue($value, $type)
    {
        return Type::getType($type)->convertToPHPValue($value, $this->getDatabasePlatform());
    }

    /**
     * Binds a set of parameters, some or all of which are typed with a PDO binding type
     * or DBAL mapping type, to a given statement.
     *
     * @param \Sojf\DBAL\Interfaces\Statement $stmt   The statement to bind the values to.
     * @param array                           $params The map/list of named/positional parameters.
     * @param array                           $types  The parameter types (PDO binding types or DBAL mapping types).
     *
     * @return void
     *
     * @internal Duck-typing used on the $stmt parameter to support driver statements as well as
     *           raw PDOStatement instances.
     */
    private function _bindTypedValues($stmt, array $params, array $types)
    {
        // Check whether parameters are positional or named. Mixing is not allowed, just like in PDO.
        if (is_int(key($params))) {

            // Positional parameters
            $typeOffset = array_key_exists(0, $types) ? -1 : 0;
            $bindIndex = 1;
            foreach ($params as $value) {

                $typeIndex = $bindIndex + $typeOffset;

                if (isset($types[$typeIndex])) {

                    $type = $types[$typeIndex];

                    list($value, $bindingType) = $this->getBindingInfo($value, $type);

                    $stmt->bindValue($bindIndex, $value, $bindingType);
                } else {

                    $stmt->bindValue($bindIndex, $value);
                }
                ++$bindIndex;
            }
        } else {
            // Named parameters
            foreach ($params as $name => $value) {

                if (isset($types[$name])) {

                    $type = $types[$name];

                    list($value, $bindingType) = $this->getBindingInfo($value, $type);

                    $stmt->bindValue($name, $value, $bindingType);

                } else {
                    $stmt->bindValue($name, $value);
                }
            }
        }
    }

    /**
     * Gets the binding type of a given type. The given type can be a PDO or DBAL mapping type.
     *
     * @param mixed $value The value to bind.
     * @param mixed $type  The type to bind (PDO or DBAL).
     *
     * @return array [0] => the (escaped) value, [1] => the binding type.
     */
    private function getBindingInfo($value, $type)
    {
        if (is_string($type)) {

            $type = Type::getType($type);
        }

        if ($type instanceof Type) {

            $value = $type->convertToDatabaseValue($value, $this->getDatabasePlatform());

            $bindingType = $type->getBindingType();
        } else {

            $bindingType = $type; // PDO::PARAM_* constants
        }

        return array($value, $bindingType);
    }

    /**
     * Resolves the parameters to a format which can be displayed.
     *
     * @internal This is a purely internal method. If you rely on this method, you are advised to
     *           copy/paste the code as this method may change, or be removed without prior notice.
     *
     * @param array $params
     * @param array $types
     *
     * @return array
     */
    public function resolveParams(array $params, array $types)
    {
        $resolvedParams = array();

        // Check whether parameters are positional or named. Mixing is not allowed, just like in PDO.
        if (is_int(key($params))) {

            // Positional parameters
            $typeOffset = array_key_exists(0, $types) ? -1 : 0;
            $bindIndex = 1;

            foreach ($params as $value) {

                $typeIndex = $bindIndex + $typeOffset;
                if (isset($types[$typeIndex])) {

                    $type = $types[$typeIndex];
                    list($value,) = $this->getBindingInfo($value, $type);
                    $resolvedParams[$bindIndex] = $value;

                } else {
                    $resolvedParams[$bindIndex] = $value;
                }
                ++$bindIndex;
            }
        } else {
            // Named parameters
            foreach ($params as $name => $value) {

                if (isset($types[$name])) {

                    $type = $types[$name];
                    list($value,) = $this->getBindingInfo($value, $type);
                    $resolvedParams[$name] = $value;

                } else {
                    $resolvedParams[$name] = $value;
                }
            }
        }

        return $resolvedParams;
    }

    /**
     * Creates a new instance of a SQL query builder.
     *
     * @return \Sojf\DBAL\Query\QueryBuilder
     */
    public function createQueryBuilder()
    {
        return new QueryBuilder($this);
    }

    /**
     * Ping the server
     *
     * When the server is not available the method returns FALSE.
     * It is responsibility of the developer to handle this case
     * and abort the request or reconnect manually:
     *
     * @example
     *
     *   if ($conn->ping() === false) {
     *      $conn->close();
     *      $conn->connect();
     *   }
     *
     * It is undefined if the underlying driver attempts to reconnect
     * or disconnect when the connection is not available anymore
     * as long it returns TRUE when a reconnect succeeded and
     * FALSE when the connection was dropped.
     *
     * @return bool
     */
    public function ping()
    {
        $this->connect();

        if ($this->_conn instanceof PingableConnection) {

            return $this->_conn->ping();
        }

        try {

            $this->query($this->getDatabasePlatform()->getDummySelectSQL());
            return true;

        } catch (DBALException $e) {
            return false;
        }
    }
}