<?php
namespace Sojf\DBAL\Interfaces;


use Sojf\DBAL\Connection as Conn;

/**
 * Driver interface.
 * Interface that all DBAL drivers must implement.
 *
 * @since 2.0
 */
interface Driver
{
    /**
     * Attempts to create a connection with the database.
     *
     * @param array       $params        All connection parameters passed by the user.
     * @param string|null $username      The username to use when connecting.
     * @param string|null $password      The password to use when connecting.
     * @param array       $driverOptions The driver options to use when connecting.
     *
     * @return \Sojf\DBAL\Interfaces\Connection The database connection.
     */
    public function connect(array $params, $username = null, $password = null, array $driverOptions = array());

    /**
     * Gets the DatabasePlatform instance that provides all the metadata about
     * the platform this driver connects to.
     *
     * @return \Sojf\DBAL\Abstracts\Platform The database platform.
     */
    public function getDatabasePlatform();

    /**
     * Gets the SchemaManager that can be used to inspect and change the underlying
     * database schema of the platform this driver connects to.
     *
     * @param \Sojf\DBAL\Connection $conn
     *
     * @return \Sojf\DBAL\Abstracts\SchemaManager
     */
    public function getSchemaManager(Conn $conn);

    /**
     * Gets the name of the driver.
     *
     * @return string The name of the driver.
     */
    public function getName();

    /**
     * Gets the name of the database connected to for this driver.
     *
     * @param \Sojf\DBAL\Connection $conn
     *
     * @return string The name of the database.
     */
    public function getDatabase(Conn $conn);
}