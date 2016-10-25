<?php
namespace Sojf\DBAL\Abstracts;

use Sojf\DBAL\Exceptions\DBAL as DBALException;
use Sojf\DBAL\Platforms\MySql as MySqlPlatform;
use Sojf\DBAL\Interfaces\DriverException;


use Sojf\DBAL\Connection;
use Sojf\DBAL\Platforms\MySQL57;
use Sojf\DBAL\Interfaces\Driver;
use Sojf\DBAL\Schema\MySqlSchemaManager;
use Sojf\DBAL\Interfaces\ExceptionConverterDriver;
use Sojf\DBAL\Interfaces\VersionAwarePlatformDriver;


/**
 * Abstract base implementation of the {@link Doctrine\DBAL\Driver} interface for MySQL based drivers.
 *
 * @author Steve Müller <st.mueller@dzh-online.de>
 * @link   www.doctrine-project.org
 * @since  2.5
 */
abstract class MySQLDriver implements Driver, ExceptionConverterDriver, VersionAwarePlatformDriver
{
    public function getDatabasePlatform()
    {
        return new MySqlPlatform();
    }

    public function getSchemaManager(Connection $conn)
    {
        return new MySqlSchemaManager($conn);
    }

    public function getDatabase(Connection $conn)
    {
        $params = $conn->getParams();

        if (isset($params['dbname'])) {
            return $params['dbname'];
        }

        return $conn->query('SELECT DATABASE()')->fetchColumn();
    }

    public function createDatabasePlatformForVersion($version)
    {
        if ( ! preg_match('/^(?P<major>\d+)(?:\.(?P<minor>\d+)(?:\.(?P<patch>\d+))?)?/', $version, $versionParts)) {

            throw DBALException::invalidPlatformVersionSpecified(
                $version,
                '<major_version>.<minor_version>.<patch_version>'
            );
        }

        if (false !== stripos($version, 'mariadb')) {

            return $this->getDatabasePlatform();
        }

        $majorVersion = $versionParts['major'];
        $minorVersion = isset($versionParts['minor']) ? $versionParts['minor'] : 0;
        $patchVersion = isset($versionParts['patch']) ? $versionParts['patch'] : 0;
        $version      = $majorVersion . '.' . $minorVersion . '.' . $patchVersion;

        if (version_compare($version, '5.7', '>=')) {

            return new MySQL57();
        }

        return $this->getDatabasePlatform();
    }

    /**
     * @link http://dev.mysql.com/doc/refman/5.7/en/error-messages-client.html
     * @link http://dev.mysql.com/doc/refman/5.7/en/error-messages-server.html
     */
    public function convertException($message, DriverException $exception)
    {
        switch ($exception->getErrorCode()) {
            case '1050':
                //return new Exception\TableExistsException($message, $exception);
                return new \Exception($message, 0, $exception);

            case '1051':
            case '1146':
                //return new Exception\TableNotFoundException($message, $exception);
                return new \Exception($message, 0, $exception);

            case '1216':
            case '1217':
            case '1451':
            case '1452':
            case '1701':
                //return new Exception\ForeignKeyConstraintViolationException($message, $exception);
                return new \Exception($message, 0, $exception);

            case '1062':
            case '1557':
            case '1569':
            case '1586':
                //return new Exception\UniqueConstraintViolationException($message, $exception);
                return new \Exception($message, 0, $exception);

            case '1054':
            case '1166':
            case '1611':
                //return new Exception\InvalidFieldNameException($message, $exception);
                return new \Exception($message, 0, $exception);

            case '1052':
            case '1060':
            case '1110':
                //return new Exception\NonUniqueFieldNameException($message, $exception);
                return new \Exception($message, 0, $exception);

            case '1064':
            case '1149':
            case '1287':
            case '1341':
            case '1342':
            case '1343':
            case '1344':
            case '1382':
            case '1479':
            case '1541':
            case '1554':
            case '1626':
                //return new Exception\SyntaxErrorException($message, $exception);
                return new \Exception($message, 0, $exception);

            case '1044':
            case '1045':
            case '1046':
            case '1049':
            case '1095':
            case '1142':
            case '1143':
            case '1227':
            case '1370':
            case '2002':
            case '2005':
                //return new Exception\ConnectionException($message, $exception);
                return new \Exception($message, 0, $exception);

            case '1048':
            case '1121':
            case '1138':
            case '1171':
            case '1252':
            case '1263':
            case '1566':
                //return new Exception\NotNullConstraintViolationException($message, $exception);
                return new \Exception($message, 0, $exception);
        }

        return new \Exception($message, 0, $exception);
        //return new Exception\DriverException($message, $exception);
    }
}
