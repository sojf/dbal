<?php
namespace Sojf\DBAL\Wrapper;

use PDO;
use PDOException;

use Sojf\DBAL\Interfaces\Connection;
use Sojf\DBAL\Interfaces\ServerInfoAwareConnection;

/**
 * PDO implementation of the Connection interface.
 * Used by all PDO-based drivers.
 */
class PDOConnection extends PDO implements Connection, ServerInfoAwareConnection
{
    /**
     * @param string      $dsn
     * @param string|null $user
     * @param string|null $password
     * @param array|null  $options
     *
     * @throws PDOException in case of an error.
     */
    public function __construct($dsn, $user = null, $password = null, array $options = null)
    {
        try {

            parent::__construct($dsn, $user, $password, $options);

            $this->setAttribute(PDO::ATTR_STATEMENT_CLASS, array('Sojf\DBAL\Wrapper\PDOStatement', array()));

            $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        } catch (PDOException $exception) {

            throw new PDOException($exception);
        }
    }

    public function exec($statement)
    {
        try {

            return parent::exec($statement);

        } catch (PDOException $exception) {

            throw new PDOException($exception);
        }
    }

    public function prepare($prepareString, $driverOptions = array())
    {
        try {

            return parent::prepare($prepareString, $driverOptions);

        } catch (PDOException $exception) {

            throw new PDOException($exception);
        }
    }

    public function query()
    {
        $args = func_get_args();
        $argsCount = count($args);

        try {
            if ($argsCount == 4) {

                return parent::query($args[0], $args[1], $args[2], $args[3]);
            }

            if ($argsCount == 3) {

                return parent::query($args[0], $args[1], $args[2]);
            }

            if ($argsCount == 2) {

                return parent::query($args[0], $args[1]);
            }

            return parent::query($args[0]);

        } catch (PDOException $exception) {

            throw new PDOException($exception);
        }
    }

    public function quote($input, $type = \PDO::PARAM_STR)
    {
        return parent::quote($input, $type);
    }

    public function lastInsertId($name = null)
    {
        return parent::lastInsertId($name);
    }

    public function getServerVersion()
    {
        return PDO::getAttribute(PDO::ATTR_SERVER_VERSION);
    }

    public function requiresQueryForServerVersion()
    {
        return false;
    }
}
