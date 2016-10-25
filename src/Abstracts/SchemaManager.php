<?php
namespace Sojf\DBAL\Abstracts;


// todo 注释事件
//use Doctrine\DBAL\Events;
//use Doctrine\DBAL\Event\SchemaColumnDefinitionEventArgs;
//use Doctrine\DBAL\Event\SchemaIndexDefinitionEventArgs;

use Sojf\DBAL\Exceptions\DBAL as DBALException;
use Sojf\DBAL\Schema\Table;
use Sojf\DBAL\Schema\View;
use Sojf\DBAL\Schema\TableDiff;
use Sojf\DBAL\Schema\Index;
use Sojf\DBAL\Schema\Sequence;
use Sojf\DBAL\Schema\Schema;
use Sojf\DBAL\Schema\SchemaConfig;
use Sojf\DBAL\Schema\ForeignKeyConstraint;

use Sojf\DBAL\Interfaces\Constraint;

/**
 * Base class for schema managers. Schema managers are used to inspect and/or
 * modify the database schema/structure.
 *
 * @author Konsta Vesterinen <kvesteri@cc.hut.fi>
 * @author Lukas Smith <smith@pooteeweet.org> (PEAR MDB2 library)
 * @author Roman Borschel <roman@code-factory.org>
 * @author Jonathan H. Wage <jonwage@gmail.com>
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 * @since  2.0
 */
abstract class SchemaManager
{
    /**
     * Holds instance of the Doctrine connection for this schema manager.
     *
     * @var \Sojf\DBAL\Connection
     */
    protected $_conn;

    /**
     * Holds instance of the database platform used for this schema manager.
     *
     * @var \Sojf\DBAL\Abstracts\Platform
     */
    protected $_platform;

    /**
     * Constructor. Accepts the Connection instance to manage the schema for.
     *
     * @param \Sojf\DBAL\Connection                      $conn
     * @param \Sojf\DBAL\Abstracts\Platform|null $platform
     */
    public function __construct(\Sojf\DBAL\Connection $conn, Platform $platform = null)
    {
        $this->_conn     = $conn;
        $this->_platform = $platform ?: $this->_conn->getDatabasePlatform();
    }

    /**
     * Returns the associated platform.
     *
     * @return \Sojf\DBAL\Abstracts\Platform
     */
    public function getDatabasePlatform()
    {
        return $this->_platform;
    }

    /**
     * Tries any method on the schema manager. Normally a method throws an
     * exception when your DBMS doesn't support it or if an error occurs.
     * This method allows you to try and method on your SchemaManager
     * instance and will return false if it does not work or is not supported.
     *
     * <code>
     * $result = $sm->tryMethod('dropView', 'view_name');
     * </code>
     *
     * @return mixed
     */
    public function tryMethod()
    {
        $args = func_get_args();
        $method = $args[0];
        unset($args[0]);
        $args = array_values($args);

        try {
            return call_user_func_array(array($this, $method), $args);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Lists the available databases for this connection.
     *
     * @return array
     * todo 获取数据库
     */
    public function listDatabases()
    {
        $sql = $this->_platform->getListDatabasesSQL();

        $databases = $this->_conn->fetchAll($sql);

        return $this->_getPortableDatabasesList($databases);
    }

    /**
     * Returns a list of all namespaces in the current database.
     *
     * @return array
     * todo 关闭命名空间列表
     */
//    public function listNamespaceNames()
//    {
//        $sql = $this->_platform->getListNamespacesSQL();
//
//        $namespaces = $this->_conn->fetchAll($sql);
//
//        return $this->getPortableNamespacesList($namespaces);
//    }

    /**
     * Lists the available sequences for this connection.
     *
     * @param string|null $database
     *
     * @return \Sojf\DBAL\Schema\Sequence[]
     * todo 关闭序列列表
     */
//    public function listSequences($database = null)
//    {
//        if (is_null($database)) {
//            $database = $this->_conn->getDatabase();
//        }
//        $sql = $this->_platform->getListSequencesSQL($database);
//
//        $sequences = $this->_conn->fetchAll($sql);
//
//        return $this->filterAssetNames($this->_getPortableSequencesList($sequences));
//    }

    /**
     * Lists the indexes for a given table returning an array of Index instances.
     *
     * Keys of the portable indexes list are all lower-cased.
     *
     * @param string $table The name of the table.
     *
     * @return \Sojf\DBAL\Schema\Index[]
     * todo 获取表索引
     */
    public function listTableIndexes($table)
    {
        $sql = $this->_platform->getListTableIndexesSQL($table, $this->_conn->getDatabase());

        $tableIndexes = $this->_conn->fetchAll($sql);

        // 解析索引
        return $this->_getPortableTableIndexesList($tableIndexes, $table);
    }

    /**
     * Aggregates and groups the index results according to the required data result.
     *
     * @param array       $tableIndexRows
     * @param string|null $tableName
     *
     * @return array
     * todo 子类MysqlSchemaManager已经覆盖此方法进行些细节调整。1.现在优化公共部分放在子类，避免多个foreach
     */
    protected function _getPortableTableIndexesList($tableIndexRows, $tableName=null)
    {
        $result = array();
        foreach ($tableIndexRows as $tableIndex) {

            $indexName = $keyName = $tableIndex['key_name'];

            if ($tableIndex['primary']) {
                $keyName = 'primary';
            }

            $keyName = strtolower($keyName);

            if (!isset($result[$keyName])) {

                $result[$keyName] = array(
                    'name' => $indexName,
                    'columns' => array($tableIndex['column_name']),
                    'unique' => $tableIndex['non_unique'] ? false : true,
                    'primary' => $tableIndex['primary'],
                    'flags' => isset($tableIndex['flags']) ? $tableIndex['flags'] : array(),
                    'options' => isset($tableIndex['where']) ? array('where' => $tableIndex['where']) : array(),
                );
            } else {

                $result[$keyName]['columns'][] = $tableIndex['column_name'];
            }
        }

        // todo 关闭事件
//        $eventManager = $this->_platform->getEventManager();

        $indexes = array();
        foreach ($result as $indexKey => $data) {

//            $index = null;
//            $defaultPrevented = false;

            // todo 关闭事件 onSchemaIndexDefinition
//            if (null !== $eventManager && $eventManager->hasListeners(Events::onSchemaIndexDefinition)) {
//                $eventArgs = new SchemaIndexDefinitionEventArgs($data, $tableName, $this->_conn);
//                $eventManager->dispatchEvent(Events::onSchemaIndexDefinition, $eventArgs);
//
//                $defaultPrevented = $eventArgs->isDefaultPrevented();
//                $index = $eventArgs->getIndex();
//            }

//            if ( ! $defaultPrevented) {
//                $index = new Index($data['name'], $data['columns'], $data['unique'], $data['primary'], $data['flags'], $data['options']);
//            }

            $index = new Index($data['name'], $data['columns'], $data['unique'], $data['primary'], $data['flags'], $data['options']);

            if ($index) {
                $indexes[$indexKey] = $index;
            }
        }

        return $indexes;
    }

    /**
     * Returns true if all the given tables exist.
     *
     * @param array $tableNames
     *
     * @return boolean
     */
    public function tablesExist($tableNames)
    {
        $tableNames = array_map('strtolower', (array) $tableNames);

        return count($tableNames) == count(\array_intersect($tableNames, array_map('strtolower', $this->listTableNames())));
    }

    /**
     * Returns a list of all tables in the current database.
     *
     * @return array
     * todo 获取数据库所有表名称
     */
    public function listTableNames()
    {
        $sql = $this->_platform->getListTablesSQL();

        $tables = $this->_conn->fetchAll($sql);

        // 解析表
        $tableNames = $this->_getPortableTablesList($tables);

        return $tableNames;
    }

    /**
     * @param array $tables
     *
     * @return array
     * todo 解析表名称
     */
    protected function _getPortableTablesList($tables)
    {
        $list = array();
        foreach ($tables as $value) {

            if ($value = $this->_getPortableTableDefinition($value)) {
                $list[] = $value;
            }
        }

        return $list;
    }

    /**
     * @param array $table
     * @return array
     * todo 子类已经覆盖此方法
     */
    protected function _getPortableTableDefinition($table)
    {
        return $table;
    }

    /**
     * Lists the tables for this connection.
     * @return \Sojf\DBAL\Schema\Table[]
     * todo 获取所有表及其栏位
     */
    public function listTables()
    {
        $tableNames = $this->listTableNames();

        $tables = array();
        foreach ($tableNames as $tableName) {
            $tables[] = $this->listTableDetails($tableName);
        }

        return $tables;
    }

    /**
     * @param string $tableName
     * @return \Sojf\DBAL\Schema\Table
     * todo 获取单个表及其栏位
     */
    public function listTableDetails($tableName)
    {
        // 获取此表所有栏位
        $columns = $this->listTableColumns($tableName);
        $foreignKeys = array();

        // 检查平台是否支持外键约束
        if ($this->_platform->supportsForeignKeyConstraints()) {

            // 获取表外键
            $foreignKeys = $this->listTableForeignKeys($tableName);
        }

        // 获取表索引
        $indexes = $this->listTableIndexes($tableName);

        return new Table($tableName, $columns, $indexes, $foreignKeys, false, array());
    }

    /**
     * Lists the columns for a given table.
     *
     * In contrast to other libraries and to the old version of Doctrine,
     * this column definition does try to contain the 'primary' field for
     * the reason that it is not portable accross different RDBMS. Use
     * {@see listTableIndexes($tableName)} to retrieve the primary key
     * of a table. We're a RDBMS specifies more details these are held
     * in the platformDetails array.
     *
     * @param string      $table    The name of the table.
     * @param string|null $database
     *
     * @return \Sojf\DBAL\Schema\Column[]
     * todo 获取表的所有栏位
     */
    public function listTableColumns($table, $database = null)
    {
        if ( ! $database) {
            $database = $this->_conn->getDatabase();
        }

        $sql = $this->_platform->getListTableColumnsSQL($table, $database);

        $tableColumns = $this->_conn->fetchAll($sql);

        return $this->_getPortableTableColumnList($tableColumns);
    }

    /**
     * Independent of the database the keys of the column list result are lowercased.
     *
     * The name of the created column instance however is kept in its case.
     *
     * @param string $table        The name of the table.
     * @param string $database
     * @param array  $tableColumns
     *
     * @return array
     * todo 栏位解析
     */
    protected function _getPortableTableColumnList($tableColumns, $table = '', $database = '')
    {
        // todo  关闭事件管理
//        $eventManager = $this->_platform->getEventManager();

        $list = array();
        foreach ($tableColumns as $tableColumn) {

//            $column = null;
//            $defaultPrevented = false;

            //todo 关闭事件管理 onSchemaColumnDefinition
//            if (null !== $eventManager && $eventManager->hasListeners(Events::onSchemaColumnDefinition)) {
//
//                $eventArgs = new SchemaColumnDefinitionEventArgs($tableColumn, $table, $database, $this->_conn);
//                $eventManager->dispatchEvent(Events::onSchemaColumnDefinition, $eventArgs);
//
//                $defaultPrevented = $eventArgs->isDefaultPrevented();
//                $column = $eventArgs->getColumn();
//            }

//            if ( ! $defaultPrevented) {
//                $column = $this->_getPortableTableColumnDefinition($tableColumn);
//            }

            // todo 调用子类栏位解析方法
            $column = $this->_getPortableTableColumnDefinition($tableColumn);

//            if ($column) {
//
//                $name = strtolower($column->getQuotedName($this->_platform));
//                $list[$name] = $column;
//            }

            $name = strtolower($column->getQuotedName($this->_platform));
            $list[$name] = $column;
        }

        return $list;
    }

    /**
     * Lists the foreign keys for the given table.
     *
     * @param string      $table    The name of the table.
     * @param string|null $database
     *
     * @return \Sojf\DBAL\Schema\ForeignKeyConstraint[]
     * todo 获取表的外键
     */
    public function listTableForeignKeys($table, $database = null)
    {
        if (is_null($database)) {
            $database = $this->_conn->getDatabase();
        }
        $sql = $this->_platform->getListTableForeignKeysSQL($table, $database);
        $tableForeignKeys = $this->_conn->fetchAll($sql);

        return $this->_getPortableTableForeignKeysList($tableForeignKeys);
    }

    /**
     * @param array $tableForeignKeys
     *
     * @return array
     * todo 外键解析
     */
    protected function _getPortableTableForeignKeysList($tableForeignKeys)
    {
        $list = array();
        foreach ($tableForeignKeys as $value) {

            if ($value = $this->_getPortableTableForeignKeyDefinition($value)) {
                $list[] = $value;
            }
        }

        return $list;
    }

    /**
     * Gets Table Column Definition.
     *
     * @param array $tableColumn
     *
     * @return \Sojf\DBAL\Schema\Column
     */
    abstract protected function _getPortableTableColumnDefinition($tableColumn);

    /**
     * Lists the views this connection has.
     *
     * @return \Sojf\DBAL\Schema\View[]
     */
    public function listViews()
    {
        $database = $this->_conn->getDatabase();
        $sql = $this->_platform->getListViewsSQL($database);
        $views = $this->_conn->fetchAll($sql);

        return $this->_getPortableViewsList($views);
    }

    /**
     * @param array $views
     *
     * @return array
     */
    protected function _getPortableViewsList($views)
    {
        $list = array();
        foreach ($views as $value) {
            if ($view = $this->_getPortableViewDefinition($value)) {
                $viewName = strtolower($view->getQuotedName($this->_platform));
                $list[$viewName] = $view;
            }
        }

        return $list;
    }

    /* drop*() Methods */

    /**
     * Drops a database.
     *
     * NOTE: You can not drop the database this SchemaManager is currently connected to.
     *
     * @param string $database The name of the database to drop.
     *
     * @return void
     */
    public function dropDatabase($database)
    {
        $this->_execSql($this->_platform->getDropDatabaseSQL($database));
    }

    /**
     * Drops the given table.
     *
     * @param string $tableName The name of the table to drop.
     *
     * @return void
     */
    public function dropTable($tableName)
    {
        $this->_execSql($this->_platform->getDropTableSQL($tableName));
    }

    /**
     * Drops the index from the given table.
     *
     * @param \Sojf\DBAL\Schema\Index|string $index The name of the index.
     * @param \Sojf\DBAL\Schema\Table|string $table The name of the table.
     *
     * @return void
     */
    public function dropIndex($index, $table)
    {
        if ($index instanceof Index) {
            $index = $index->getQuotedName($this->_platform);
        }

        $this->_execSql($this->_platform->getDropIndexSQL($index, $table));
    }

    /**
     * Drops the constraint from the given table.
     *
     * @param \Sojf\DBAL\Interfaces\Constraint   $constraint
     * @param \Sojf\DBAL\Schema\Table|string $table      The name of the table.
     *
     * @return void
     */
    public function dropConstraint(Constraint $constraint, $table)
    {
        $this->_execSql($this->_platform->getDropConstraintSQL($constraint, $table));
    }

    /**
     * Drops a foreign key from a table.
     *
     * @param \Sojf\DBAL\Schema\ForeignKeyConstraint|string $foreignKey The name of the foreign key.
     * @param \Sojf\DBAL\Schema\Table|string                $table      The name of the table with the foreign key.
     *
     * @return void
     */
    public function dropForeignKey($foreignKey, $table)
    {
        $this->_execSql($this->_platform->getDropForeignKeySQL($foreignKey, $table));
    }

    /**
     * Drops a sequence with a given name.
     *
     * @param string $name The name of the sequence to drop.
     *
     * @return void
     */
    public function dropSequence($name)
    {
        $this->_execSql($this->_platform->getDropSequenceSQL($name));
    }

    /**
     * Drops a view.
     *
     * @param string $name The name of the view.
     *
     * @return void
     */
    public function dropView($name)
    {
        $this->_execSql($this->_platform->getDropViewSQL($name));
    }

    /* create*() Methods */

    /**
     * Creates a new database.
     *
     * @param string $database The name of the database to create.
     *
     * @return void
     */
    public function createDatabase($database)
    {
        $this->_execSql($this->_platform->getCreateDatabaseSQL($database));
    }

    /**
     * Creates a new table.
     *
     * @param \Sojf\DBAL\Schema\Table $table
     *
     * @return void
     */
    public function createTable(Table $table)
    {
        $createFlags = Platform::CREATE_INDEXES|Platform::CREATE_FOREIGNKEYS;

        $this->_execSql($this->_platform->getCreateTableSQL($table, $createFlags));
    }

    /**
     * Creates a new sequence.
     *
     * @param \Sojf\DBAL\Schema\Sequence $sequence
     *
     * @return void
     *
     * @throws \Doctrine\DBAL\ConnectionException If something fails at database level.
     */
    public function createSequence($sequence)
    {
        $this->_execSql($this->_platform->getCreateSequenceSQL($sequence));
    }

    /**
     * Creates a constraint on a table.
     *
     * @param \Sojf\DBAL\Interfaces\Constraint   $constraint
     * @param \Sojf\DBAL\Schema\Table|string $table
     *
     * @return void
     */
    public function createConstraint(Constraint $constraint, $table)
    {
        $this->_execSql($this->_platform->getCreateConstraintSQL($constraint, $table));
    }

    /**
     * Creates a new index on a table.
     *
     * @param \Sojf\DBAL\Schema\Index        $index
     * @param \Sojf\DBAL\Schema\Table|string $table The name of the table on which the index is to be created.
     *
     * @return void
     */
    public function createIndex(Index $index, $table)
    {
        $this->_execSql($this->_platform->getCreateIndexSQL($index, $table));
    }

    /**
     * Creates a new foreign key.
     *
     * @param \Sojf\DBAL\Schema\ForeignKeyConstraint $foreignKey The ForeignKey instance.
     * @param \Sojf\DBAL\Schema\Table|string         $table      The name of the table on which the foreign key is to be created.
     *
     * @return void
     */
    public function createForeignKey(ForeignKeyConstraint $foreignKey, $table)
    {
        $this->_execSql($this->_platform->getCreateForeignKeySQL($foreignKey, $table));
    }

    /**
     * Creates a new view.
     *
     * @param \Sojf\DBAL\Schema\View $view
     *
     * @return void
     */
    public function createView(View $view)
    {
        $this->_execSql($this->_platform->getCreateViewSQL($view->getQuotedName($this->_platform), $view->getSql()));
    }

    /* dropAndCreate*() Methods */

    /**
     * Drops and creates a constraint.
     *
     * @see dropConstraint()
     * @see createConstraint()
     *
     * @param \Sojf\DBAL\Interfaces\Constraint   $constraint
     * @param \Sojf\DBAL\Schema\Table|string $table
     *
     * @return void
     */
    public function dropAndCreateConstraint(Constraint $constraint, $table)
    {
        $this->tryMethod('dropConstraint', $constraint, $table);
        $this->createConstraint($constraint, $table);
    }

    /**
     * Drops and creates a new index on a table.
     *
     * @param \Sojf\DBAL\Schema\Index        $index
     * @param \Sojf\DBAL\Schema\Table|string $table The name of the table on which the index is to be created.
     *
     * @return void
     */
    public function dropAndCreateIndex(Index $index, $table)
    {
        $this->tryMethod('dropIndex', $index->getQuotedName($this->_platform), $table);
        $this->createIndex($index, $table);
    }

    /**
     * Drops and creates a new foreign key.
     *
     * @param \Sojf\DBAL\Schema\ForeignKeyConstraint $foreignKey An associative array that defines properties of the foreign key to be created.
     * @param \Sojf\DBAL\Schema\Table|string         $table      The name of the table on which the foreign key is to be created.
     *
     * @return void
     */
    public function dropAndCreateForeignKey(ForeignKeyConstraint $foreignKey, $table)
    {
        $this->tryMethod('dropForeignKey', $foreignKey, $table);
        $this->createForeignKey($foreignKey, $table);
    }

    /**
     * Drops and create a new sequence.
     *
     * @param \Sojf\DBAL\Schema\Sequence $sequence
     *
     * @return void
     *
     * @throws \Sojf\DBAL\Exceptions\Connection If something fails at database level.
     */
    public function dropAndCreateSequence(Sequence $sequence)
    {
        $this->tryMethod('dropSequence', $sequence->getQuotedName($this->_platform));
        $this->createSequence($sequence);
    }

    /**
     * Drops and creates a new table.
     *
     * @param \Sojf\DBAL\Schema\Table $table
     *
     * @return void
     */
    public function dropAndCreateTable(Table $table)
    {
        $this->tryMethod('dropTable', $table->getQuotedName($this->_platform));
        $this->createTable($table);
    }

    /**
     * Drops and creates a new database.
     *
     * @param string $database The name of the database to create.
     *
     * @return void
     */
    public function dropAndCreateDatabase($database)
    {
        $this->tryMethod('dropDatabase', $database);
        $this->createDatabase($database);
    }

    /**
     * Drops and creates a new view.
     *
     * @param \Sojf\DBAL\Schema\View $view
     *
     * @return void
     */
    public function dropAndCreateView(View $view)
    {
        $this->tryMethod('dropView', $view->getQuotedName($this->_platform));
        $this->createView($view);
    }

    /* alterTable() Methods */

    /**
     * Alters an existing tables schema.
     *
     * @param \Sojf\DBAL\Schema\TableDiff $tableDiff
     *
     * @return void
     * todo 修改表
     */
    public function alterTable(TableDiff $tableDiff)
    {
        $queries = $this->_platform->getAlterTableSQL($tableDiff);

        if (is_array($queries) && count($queries)) {

            foreach ($queries as $ddlQuery) {

                $this->_execSql($ddlQuery);
            }
        }
    }

    /**
     * Renames a given table to another name.
     *
     * @param string $name    The current name of the table.
     * @param string $newName The new name of the table.
     *
     * @return void
     */
    public function renameTable($name, $newName)
    {
        $tableDiff = new TableDiff($name);
        $tableDiff->newName = $newName;
        $this->alterTable($tableDiff);
    }

    /**
     * Methods for filtering return values of list*() methods to convert
     * the native DBMS data definition to a portable Doctrine definition
     */

    /**
     * @param array $databases
     *
     * @return array
     */
    protected function _getPortableDatabasesList($databases)
    {
        $list = array();
        foreach ($databases as $value) {
            if ($value = $this->_getPortableDatabaseDefinition($value)) {
                $list[] = $value;
            }
        }

        return $list;
    }

    /**
     * @param array $database
     *
     * @return mixed
     */
    protected function _getPortableDatabaseDefinition($database)
    {
        return $database;
    }

    /**
     * Converts a list of namespace names from the native DBMS data definition to a portable Doctrine definition.
     *
     * @param array $namespaces The list of namespace names in the native DBMS data definition.
     *
     * @return array
     */
    protected function getPortableNamespacesList(array $namespaces)
    {
        $namespacesList = array();

        foreach ($namespaces as $namespace) {
            $namespacesList[] = $this->getPortableNamespaceDefinition($namespace);
        }

        return $namespacesList;
    }

    /**
     * Converts a namespace definition from the native DBMS data definition to a portable Doctrine definition.
     *
     * @param array $namespace The native DBMS namespace definition.
     *
     * @return mixed
     */
    protected function getPortableNamespaceDefinition(array $namespace)
    {
        return $namespace;
    }

    /**
     * @param array $functions
     *
     * @return array
     */
    protected function _getPortableFunctionsList($functions)
    {
        $list = array();
        foreach ($functions as $value) {
            if ($value = $this->_getPortableFunctionDefinition($value)) {
                $list[] = $value;
            }
        }

        return $list;
    }

    /**
     * @param array $function
     *
     * @return mixed
     */
    protected function _getPortableFunctionDefinition($function)
    {
        return $function;
    }

    /**
     * @param array $triggers
     *
     * @return array
     */
    protected function _getPortableTriggersList($triggers)
    {
        $list = array();
        foreach ($triggers as $value) {
            if ($value = $this->_getPortableTriggerDefinition($value)) {
                $list[] = $value;
            }
        }

        return $list;
    }

    /**
     * @param array $trigger
     *
     * @return mixed
     */
    protected function _getPortableTriggerDefinition($trigger)
    {
        return $trigger;
    }

    /**
     * @param array $sequences
     *
     * @return array
     */
    protected function _getPortableSequencesList($sequences)
    {
        $list = array();
        foreach ($sequences as $value) {
            if ($value = $this->_getPortableSequenceDefinition($value)) {
                $list[] = $value;
            }
        }

        return $list;
    }

    /**
     * @param array $sequence
     *
     * @return \Sojf\DBAL\Schema\Sequence
     *
     * @throws DBALException
     */
    protected function _getPortableSequenceDefinition($sequence)
    {
        throw DBALException::notSupported('Sequences');
    }

    /**
     * @param array $users
     *
     * @return array
     */
    protected function _getPortableUsersList($users)
    {
        $list = array();
        foreach ($users as $value) {
            if ($value = $this->_getPortableUserDefinition($value)) {
                $list[] = $value;
            }
        }

        return $list;
    }

    /**
     * @param array $user
     *
     * @return mixed
     */
    protected function _getPortableUserDefinition($user)
    {
        return $user;
    }

    /**
     * @param array $view
     *
     * @return mixed
     */
    protected function _getPortableViewDefinition($view)
    {
        return false;
    }

    /**
     * @param array $tableForeignKey
     *
     * @return mixed
     */
    protected function _getPortableTableForeignKeyDefinition($tableForeignKey)
    {
        return $tableForeignKey;
    }

    /**
     * @param array|string $sql
     *
     * @return void
     */
    protected function _execSql($sql)
    {
        foreach ((array) $sql as $query) {
            $this->_conn->executeUpdate($query);
        }
    }

    /**
     * Creates a schema instance for the current database.
     *
     * @return \Sojf\DBAL\Schema\Schema
     */
    public function createSchema()
    {
        $namespaces = array();

        //todo: 关闭命名空间列表
//        if ($this->_platform->supportsSchemas()) {
//            $namespaces = $this->listNamespaceNames();
//        }

        $sequences = array();

        //todo: 关闭序列列表
//        if ($this->_platform->supportsSequences()) {
//            $sequences = $this->listSequences();
//        }

        $tables = $this->listTables();

        return new Schema($tables, $sequences, $this->createSchemaConfig(), $namespaces);
    }

    /**
     * Creates the configuration for this schema.
     *
     * @return \Sojf\DBAL\Schema\SchemaConfig
     */
    public function createSchemaConfig()
    {
        $schemaConfig = new SchemaConfig();
        $schemaConfig->setMaxIdentifierLength($this->_platform->getMaxIdentifierLength());

        $searchPaths = $this->getSchemaSearchPaths();
        if (isset($searchPaths[0])) {
            $schemaConfig->setName($searchPaths[0]);
        }

        $params = $this->_conn->getParams();
        if (isset($params['defaultTableOptions'])) {
            $schemaConfig->setDefaultTableOptions($params['defaultTableOptions']);
        }

        return $schemaConfig;
    }

    /**
     * The search path for namespaces in the currently connected database.
     *
     * The first entry is usually the default namespace in the Schema. All
     * further namespaces contain tables/sequences which can also be addressed
     * with a short, not full-qualified name.
     *
     * For databases that don't support subschema/namespaces this method
     * returns the name of the currently connected database.
     *
     * @return array
     */
    public function getSchemaSearchPaths()
    {
        return array($this->_conn->getDatabase());
    }

    /**
     * Given a table comment this method tries to extract a typehint for Doctrine Type, or returns
     * the type given as default.
     *
     * @param string $comment
     * @param string $currentType
     *
     * @return string
     */
    public function extractDoctrineTypeFromComment($comment, $currentType)
    {
        if (preg_match("(\(DC2Type:([a-zA-Z0-9_]+)\))", $comment, $match)) {
            $currentType = $match[1];
        }

        return $currentType;
    }

    /**
     * @param string $comment
     * @param string $type
     *
     * @return string
     */
    public function removeDoctrineTypeFromComment($comment, $type)
    {
        return str_replace('(DC2Type:'.$type.')', '', $comment);
    }
}
