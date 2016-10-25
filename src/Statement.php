<?php
namespace Sojf\DBAL;

use PDO;
use Sojf\DBAL\Abstracts\Type;
use Sojf\DBAL\Interfaces\Statement as StatementInterface;
use Sojf\DBAL\Exceptions\DBAL as DBALException;

/**
 * A thin wrapper around a Sojf\DBAL\Interfaces\Statement that adds support
 * for logging, DBAL mapping types, etc.
 *
 * @author Roman Borschel <roman@code-factory.org>
 * @since 2.0
 */
class Statement implements \IteratorAggregate, StatementInterface
{
    /**
     * The SQL statement.
     *
     * @var string
     */
    protected $sql;

    /**
     * The bound parameters.
     *
     * @var array
     */
    protected $params = array();

    /**
     * The parameter types.
     *
     * @var array
     */
    protected $types = array();

    /**
     * The underlying driver statement.
     *
     * @var \Sojf\DBAL\Interfaces\Statement
     */
    protected $stmt;

    /**
     * The underlying database platform.
     *
     * @var \Sojf\DBAL\Abstracts\Platform
     */
    protected $platform;

    /**
     * The connection this statement is bound to and executed on.
     *
     * @var \Sojf\DBAL\Connection
     */
    protected $conn;

    /**
     * Creates a new <tt>Statement</tt> for the given SQL and <tt>Connection</tt>.
     *
     * @param string                    $sql  The SQL of the statement.
     * @param \Sojf\DBAL\Connection $conn The connection on which the statement should be executed.
     */
    public function __construct($sql, Connection $conn)
    {
        $this->sql = $sql;
        $this->stmt = $conn->getWrappedConnection()->prepare($sql);
        $this->conn = $conn;
        $this->platform = $conn->getDatabasePlatform();
    }

    /**
     * Binds a parameter value to the statement.
     *
     * The value can optionally be bound with a PDO binding type or a DBAL mapping type.
     * If bound with a DBAL mapping type, the binding type is derived from the mapping
     * type and the value undergoes the conversion routines of the mapping type before
     * being bound.
     *
     * @param string $name  The name or position of the parameter.
     * @param mixed  $value The value of the parameter.
     * @param mixed  $type  Either a PDO binding type or a DBAL mapping type name or instance.
     *
     * @return boolean TRUE on success, FALSE on failure.
     */
    public function bindValue($name, $value, $type = null)
    {
        $this->params[$name] = $value;
        $this->types[$name] = $type;

        if ($type !== null) {

            if (is_string($type)) {

                $type = Type::getType($type);
            }

            if ($type instanceof Type) {

                $value = $type->convertToDatabaseValue($value, $this->platform);
                $bindingType = $type->getBindingType();
            } else {

                $bindingType = $type; // PDO::PARAM_* constants
            }

            return $this->stmt->bindValue($name, $value, $bindingType);
        } else {
            return $this->stmt->bindValue($name, $value);
        }
    }

    /**
     * Binds a parameter to a value by reference.
     *
     * Binding a parameter by reference does not support DBAL mapping types.
     *
     * @param string       $name   The name or position of the parameter.
     * @param mixed        $var    The reference to the variable to bind.
     * @param integer      $type   The PDO binding type.
     * @param integer|null $length Must be specified when using an OUT bind
     *                             so that PHP allocates enough memory to hold the returned value.
     *
     * @return boolean TRUE on success, FALSE on failure.
     */
    public function bindParam($name, &$var, $type = PDO::PARAM_STR, $length = null)
    {
        return $this->stmt->bindParam($name, $var, $type, $length);
    }

    /**
     * Executes the statement with the currently bound parameters.
     *
     * @param array|null $params
     *
     * @return boolean TRUE on success, FALSE on failure.
     *
     * @throws DBALException
     */
    public function execute($params = null)
    {
        if (is_array($params)) {
            $this->params = $params;
        }

        // todo: 关闭日志
//        $logger = $this->conn->getConfiguration()->getSQLLogger();
//        if ($logger) {
//            $logger->startQuery($this->sql, $this->params, $this->types);
//        }

        try {
            $stmt = $this->stmt->execute($params);
        } catch (\Exception $ex) {

            // todo: 关闭日志
//            if ($logger) {
//                $logger->stopQuery();
//            }
            throw DBALException::driverExceptionDuringQuery(
                $this->conn->getDriver(),
                $ex,
                $this->sql,
                $this->conn->resolveParams($this->params, $this->types)
            );
        }

        // todo: 关闭日志
//        if ($logger) {
//            $logger->stopQuery();
//        }

        $this->params = array();
        $this->types = array();

        return $stmt;
    }

    /**
     * Closes the cursor, freeing the database resources used by this statement.
     *
     * @return boolean TRUE on success, FALSE on failure.
     */
    public function closeCursor()
    {
        return $this->stmt->closeCursor();
    }

    /**
     * Returns the number of columns in the result set.
     *
     * @return integer
     */
    public function columnCount()
    {
        return $this->stmt->columnCount();
    }

    /**
     * Fetches the SQLSTATE associated with the last operation on the statement.
     *
     * @return string
     */
    public function errorCode()
    {
        return $this->stmt->errorCode();
    }

    /**
     * Fetches extended error information associated with the last operation on the statement.
     *
     * @return array
     */
    public function errorInfo()
    {
        return $this->stmt->errorInfo();
    }

    public function setFetchMode($fetchMode, $arg2 = null, $arg3 = null)
    {
        if ($arg2 === null) {
            return $this->stmt->setFetchMode($fetchMode);
        } elseif ($arg3 === null) {
            return $this->stmt->setFetchMode($fetchMode, $arg2);
        }

        return $this->stmt->setFetchMode($fetchMode, $arg2, $arg3);
    }

    /**
     * Required by interface IteratorAggregate.
     */
    public function getIterator()
    {
        return $this->stmt;
    }

    /**
     * Fetches the next row from a result set.
     *
     * @param integer|null $fetchMode
     *
     * @return mixed The return value of this function on success depends on the fetch type.
     *               In all cases, FALSE is returned on failure.
     */
    public function fetch($fetchMode = null)
    {
        return $this->stmt->fetch($fetchMode);
    }

    /**
     * Returns an array containing all of the result set rows.
     *
     * @param integer|null $fetchMode
     * @param mixed        $fetchArgument
     *
     * @return array An array containing all of the remaining rows in the result set.
     */
    public function fetchAll($fetchMode = null, $fetchArgument = 0)
    {
        if ($fetchArgument !== 0) {
            return $this->stmt->fetchAll($fetchMode, $fetchArgument);
        }

        return $this->stmt->fetchAll($fetchMode);
    }

    /**
     * Returns a single column from the next row of a result set.
     *
     * @param integer $columnIndex
     *
     * @return mixed A single column from the next row of a result set or FALSE if there are no more rows.
     */
    public function fetchColumn($columnIndex = 0)
    {
        return $this->stmt->fetchColumn($columnIndex);
    }

    /**
     * Returns the number of rows affected by the last execution of this statement.
     *
     * @return integer The number of affected rows.
     */
    public function rowCount()
    {
        return $this->stmt->rowCount();
    }

    /**
     * Gets the wrapped driver statement.
     *
     * @return \Sojf\DBAL\Interfaces\Statement
     */
    public function getWrappedStatement()
    {
        return $this->stmt;
    }
}
