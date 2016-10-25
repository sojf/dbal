<?php
namespace Sojf\DBAL\Exceptions;


use Sojf\DBAL\Interfaces\Driver;
use Sojf\DBAL\Interfaces\DriverException;
use Sojf\DBAL\Interfaces\ExceptionConverterDriver;

class DBAL extends \Exception
{
    /**
     * @param string $method
     *
     * @return DBAL
     */
    public static function notSupported($method)
    {
        return new self("Operation '$method' is not supported by platform.");
    }

    /**
     * @return DBAL
     */
    public static function invalidPlatformSpecified()
    {
        return new self(
            "Invalid 'platform' option specified, need to give an instance of ".
            "\Doctrine\DBAL\Platforms\AbstractPlatform.");
    }

    /**
     * Returns a new instance for an invalid specified platform version.
     *
     * @param string $version        The invalid platform version given.
     * @param string $expectedFormat The expected platform version format.
     *
     * @return DBAL
     */
    public static function invalidPlatformVersionSpecified($version, $expectedFormat)
    {
        return new self(
            sprintf(
                'Invalid platform version "%s" specified. ' .
                'The platform version has to be specified in the format: "%s".',
                $version,
                $expectedFormat
            )
        );
    }

    /**
     * @return DBAL
     */
    public static function invalidPdoInstance()
    {
        return new self(
            "The 'pdo' option was used in DriverManager::getConnection() but no ".
            "instance of PDO was given."
        );
    }

    /**
     * @return DBAL
     */
    public static function driverRequired()
    {
        return new self("The options 'driver' or 'driverClass' are mandatory if no PDO ".
            "instance is given to DriverManager::getConnection().");
    }

    /**
     * @param string $unknownDriverName
     * @param array  $knownDrivers
     *
     * @return DBAL
     */
    public static function unknownDriver($unknownDriverName, array $knownDrivers)
    {
        return new self("The given 'driver' ".$unknownDriverName." is unknown, ".
            "Doctrine currently supports only the following drivers: ".implode(", ", $knownDrivers));
    }

    /**
     * @param \Doctrine\DBAL\Driver     $driver
     * @param \Exception $driverEx
     * @param string     $sql
     * @param array      $params
     *
     * @return DBAL
     */
    public static function driverExceptionDuringQuery(Driver $driver, \Exception $driverEx, $sql, array $params = array())
    {
        $msg = "An exception occurred while executing '".$sql."'";
        if ($params) {
            $msg .= " with params " . self::formatParameters($params);
        }
        $msg .= ":\n\n".$driverEx->getMessage();

        if ($driver instanceof ExceptionConverterDriver && $driverEx instanceof DriverException) {
            return $driver->convertException($msg, $driverEx);
        }

        return new self($msg, 0, $driverEx);
    }

    /**
     * @param \Doctrine\DBAL\Driver     $driver
     * @param \Exception $driverEx
     *
     * @return DBAL
     */
    public static function driverException(Driver $driver, \Exception $driverEx)
    {
        $msg = "An exception occured in driver: " . $driverEx->getMessage();

        if ($driver instanceof ExceptionConverterDriver && $driverEx instanceof DriverException) {
            return $driver->convertException($msg, $driverEx);
        }

        return new self($msg, 0, $driverEx);
    }

    /**
     * Returns a human-readable representation of an array of parameters.
     * This properly handles binary data by returning a hex representation.
     *
     * @param array $params
     *
     * @return string
     */
    private static function formatParameters(array $params)
    {
        return '[' . implode(', ', array_map(function ($param) {
            $json = @json_encode($param);

            if (! is_string($json) || $json == 'null' && is_string($param)) {
                // JSON encoding failed, this is not a UTF-8 string.
                return '"\x' . implode('\x', str_split(bin2hex($param), 2)) . '"';
            }

            return $json;
        }, $params)) . ']';
    }

    /**
     * @param string $wrapperClass
     *
     * @return DBAL
     */
    public static function invalidWrapperClass($wrapperClass)
    {
        return new self("The given 'wrapperClass' ".$wrapperClass." has to be a ".
            "subtype of \Doctrine\DBAL\Connection.");
    }

    /**
     * @param string $driverClass
     *
     * @return DBAL
     */
    public static function invalidDriverClass($driverClass)
    {
        return new self("The given 'driverClass' ".$driverClass." has to implement the ".
            "\Doctrine\DBAL\Driver interface.");
    }

    /**
     * @param string $tableName
     *
     * @return DBAL
     */
    public static function invalidTableName($tableName)
    {
        return new self("Invalid table name specified: ".$tableName);
    }

    /**
     * @param string $tableName
     *
     * @return DBAL
     */
    public static function noColumnsSpecifiedForTable($tableName)
    {
        return new self("No columns specified for table ".$tableName);
    }

    /**
     * @return DBAL
     */
    public static function limitOffsetInvalid()
    {
        return new self("Invalid Offset in Limit Query, it has to be larger or equal to 0.");
    }

    /**
     * @param string $name
     *
     * @return DBAL
     */
    public static function typeExists($name)
    {
        return new self('Type '.$name.' already exists.');
    }

    /**
     * @param string $name
     *
     * @return DBAL
     */
    public static function unknownColumnType($name)
    {
        return new self('Unknown column type "'.$name.'" requested. Any Doctrine type that you use has ' .
            'to be registered with \Doctrine\DBAL\Types\Type::addType(). You can get a list of all the ' .
            'known types with \Doctrine\DBAL\Types\Type::getTypesMap(). If this error occurs during database ' .
            'introspection then you might have forgot to register all database types for a Doctrine Type. Use ' .
            'AbstractPlatform#registerDoctrineTypeMapping() or have your custom types implement ' .
            'Type#getMappedDatabaseTypes(). If the type name is empty you might ' .
            'have a problem with the cache or forgot some mapping information.'
        );
    }

    /**
     * @param string $name
     *
     * @return DBAL
     */
    public static function typeNotFound($name)
    {
        return new self('Type to be overwritten '.$name.' does not exist.');
    }
}
