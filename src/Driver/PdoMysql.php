<?php
namespace Sojf\DBAL\Driver;


use PDOException;
use Sojf\DBAL\Abstracts\MySQLDriver;
use Sojf\DBAL\Wrapper\PDOConnection;

class PdoMysql extends MySQLDriver
{
    public function connect(array $params, $username = null, $password = null, array $driverOptions = array())
    {
        try {
            $conn = new PDOConnection(
                $this->constructPdoDsn($params),
                $username,
                $password,
                $driverOptions
            );
        } catch (PDOException $e) {

            throw new \Exception('An exception occured in driver: ' . $e->getMessage());
        }

        return $conn;
    }

    /**
     * Constructs the MySql PDO DSN.
     *
     * @param array $params
     *
     * @return string The DSN.
     */
    protected function constructPdoDsn(array $params)
    {
        $dsn = 'mysql:';
        
        if (isset($params['host']) && $params['host'] != '') {
            $dsn .= 'host=' . $params['host'] . ';';
        }
        
        if (isset($params['port'])) {
            $dsn .= 'port=' . $params['port'] . ';';
        }
        
        if (isset($params['dbname'])) {
            $dsn .= 'dbname=' . $params['dbname'] . ';';
        }
        
        if (isset($params['unix_socket'])) {
            $dsn .= 'unix_socket=' . $params['unix_socket'] . ';';
        }
        
        if (isset($params['charset'])) {
            $dsn .= 'charset=' . $params['charset'] . ';';
        }

        return $dsn;
    }

    public function getName()
    {
        return 'pdo_mysql';
    }
}
