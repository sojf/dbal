<?php
namespace Sojf\DBAL\Platforms;


use Sojf\DBAL\Schema\Identifier;
use Sojf\DBAL\Schema\TableDiff;
use Sojf\DBAL\Schema\Index;
use Sojf\DBAL\Schema\Table;
use Sojf\DBAL\Schema\ForeignKeyConstraint;
use Sojf\DBAL\Types\BlobType;
use Sojf\DBAL\Types\TextType;


use Sojf\DBAL\Abstracts\Platform;


class MySql extends Platform
{
    const LENGTH_LIMIT_TINYTEXT   = 255;
    const LENGTH_LIMIT_TEXT       = 65535;
    const LENGTH_LIMIT_MEDIUMTEXT = 16777215;

    const LENGTH_LIMIT_TINYBLOB   = 255;
    const LENGTH_LIMIT_BLOB       = 65535;
    const LENGTH_LIMIT_MEDIUMBLOB = 16777215;


    // todo  初始化字段类型
    protected function initializeDoctrineTypeMappings()
    {
        $this->doctrineTypeMapping = array(
            'tinyint'       => 'boolean',
            'smallint'      => 'smallint',
            'mediumint'     => 'integer',
            'int'           => 'integer',
            'integer'       => 'integer',
            'bigint'        => 'bigint',
            'tinytext'      => 'text',
            'mediumtext'    => 'text',
            'longtext'      => 'text',
            'text'          => 'text',
            'varchar'       => 'string',
            'string'        => 'string',
            'char'          => 'string',
            'date'          => 'date',
            'datetime'      => 'datetime',
            'timestamp'     => 'datetime',
            'time'          => 'time',
            'float'         => 'float',
            'double'        => 'float',
            'real'          => 'float',
            'decimal'       => 'decimal',
            'numeric'       => 'decimal',
            'year'          => 'date',
            'longblob'      => 'blob',
            'blob'          => 'blob',
            'mediumblob'    => 'blob',
            'tinyblob'      => 'blob',
            'binary'        => 'binary',
            'varbinary'     => 'binary',
            'set'           => 'set',
            'enum'           => 'enum'
        );
    }

    /**
     * Adds MySQL-specific LIMIT clause to the query
     * 18446744073709551615 is 2^64-1 maximum of unsigned BIGINT the biggest limit possible
     */
    protected function doModifyLimitQuery($query, $limit, $offset)
    {
        if ($limit !== null) {
            $query .= ' LIMIT ' . $limit;
            if ($offset !== null) {
                $query .= ' OFFSET ' . $offset;
            }
        } elseif ($offset !== null) {
            $query .= ' LIMIT 18446744073709551615 OFFSET ' . $offset;
        }

        return $query;
    }

    public function getIdentifierQuoteCharacter()
    {
        return '`';
    }

    public function getRegexpExpression()
    {
        return 'RLIKE';
    }

    public function getGuidExpression()
    {
        return 'UUID()';
    }

    public function getLocateExpression($str, $substr, $startPos = false)
    {
        if ($startPos == false) {
            return 'LOCATE(' . $substr . ', ' . $str . ')';
        }

        return 'LOCATE(' . $substr . ', ' . $str . ', '.$startPos.')';
    }

    public function getConcatExpression()
    {
        $args = func_get_args();

        return 'CONCAT(' . join(', ', (array) $args) . ')';
    }

    protected function getDateArithmeticIntervalExpression($date, $operator, $interval, $unit)
    {
        $function = '+' === $operator ? 'DATE_ADD' : 'DATE_SUB';

        return $function . '(' . $date . ', INTERVAL ' . $interval . ' ' . $unit . ')';
    }

    public function getDateDiffExpression($date1, $date2)
    {
        return 'DATEDIFF(' . $date1 . ', ' . $date2 . ')';
    }

    public function getListDatabasesSQL()
    {
        return 'SHOW DATABASES';
    }

    public function getListTableConstraintsSQL($table)
    {
        return 'SHOW INDEX FROM ' . $table;
    }

    /**
     * Two approaches to listing the table indexes. The information_schema is
     * preferred, because it doesn't cause problems with SQL keywords such as "order" or "table".
     */
    public function getListTableIndexesSQL($table, $currentDatabase = null)
    {
        if ($currentDatabase) {
            return "SELECT TABLE_NAME AS `Table`, NON_UNIQUE AS Non_Unique, INDEX_NAME AS Key_name, ".
                   "SEQ_IN_INDEX AS Seq_in_index, COLUMN_NAME AS Column_Name, COLLATION AS Collation, ".
                   "CARDINALITY AS Cardinality, SUB_PART AS Sub_Part, PACKED AS Packed, " .
                   "NULLABLE AS `Null`, INDEX_TYPE AS Index_Type, COMMENT AS Comment " .
                   "FROM information_schema.STATISTICS WHERE TABLE_NAME = '" . $table . "' AND TABLE_SCHEMA = '" . $currentDatabase . "'";
        }

        return 'SHOW INDEX FROM ' . $table;
    }

    public function getListViewsSQL($database)
    {
        return "SELECT * FROM information_schema.VIEWS WHERE TABLE_SCHEMA = '".$database."'";
    }

    public function getListTableForeignKeysSQL($table, $database = null)
    {
        $sql = "SELECT DISTINCT k.`CONSTRAINT_NAME`, k.`COLUMN_NAME`, k.`REFERENCED_TABLE_NAME`, ".
               "k.`REFERENCED_COLUMN_NAME` /*!50116 , c.update_rule, c.delete_rule */ ".
               "FROM information_schema.key_column_usage k /*!50116 ".
               "INNER JOIN information_schema.referential_constraints c ON ".
               "  c.constraint_name = k.constraint_name AND ".
               "  c.table_name = '$table' */ WHERE k.table_name = '$table'";

        if ($database) {
            $sql .= " AND k.table_schema = '$database' /*!50116 AND c.constraint_schema = '$database' */";
        }

        $sql .= " AND k.`REFERENCED_COLUMN_NAME` is not NULL";

        return $sql;
    }

    public function getCreateViewSQL($name, $sql)
    {
        return 'CREATE VIEW ' . $name . ' AS ' . $sql;
    }

    public function getDropViewSQL($name)
    {
        return 'DROP VIEW '. $name;
    }

    protected function getVarcharTypeDeclarationSQLSnippet($length, $fixed)
    {
        return $fixed ? ($length ? 'CHAR(' . $length . ')' : 'CHAR(255)')
                : ($length ? 'VARCHAR(' . $length . ')' : 'VARCHAR(255)');
    }

    protected function getBinaryTypeDeclarationSQLSnippet($length, $fixed)
    {
        return $fixed ? 'BINARY(' . ($length ?: 255) . ')' : 'VARBINARY(' . ($length ?: 255) . ')';
    }

    /**
     * Gets the SQL snippet used to declare a CLOB column type.
     *     TINYTEXT   : 2 ^  8 - 1 = 255
     *     TEXT       : 2 ^ 16 - 1 = 65535
     *     MEDIUMTEXT : 2 ^ 24 - 1 = 16777215
     *     LONGTEXT   : 2 ^ 32 - 1 = 4294967295
     *
     * @param array $field
     *
     * @return string
     */
    public function getClobTypeDeclarationSQL(array $field)
    {
        if ( ! empty($field['length']) && is_numeric($field['length'])) {
            $length = $field['length'];

            if ($length <= static::LENGTH_LIMIT_TINYTEXT) {
                return 'TINYTEXT';
            }

            if ($length <= static::LENGTH_LIMIT_TEXT) {
                return 'TEXT';
            }

            if ($length <= static::LENGTH_LIMIT_MEDIUMTEXT) {
                return 'MEDIUMTEXT';
            }
        }

        return 'LONGTEXT';
    }

    public function getDateTimeTypeDeclarationSQL(array $fieldDeclaration)
    {
        if (isset($fieldDeclaration['version']) && $fieldDeclaration['version'] == true) {
            return 'TIMESTAMP';
        }

        return 'DATETIME';
    }

    public function getDateTypeDeclarationSQL(array $fieldDeclaration)
    {
        return 'DATE';
    }

    public function getTimeTypeDeclarationSQL(array $fieldDeclaration)
    {
        return 'TIME';
    }

    public function getBooleanTypeDeclarationSQL(array $field)
    {
        return 'TINYINT(1)';
    }

    /**
     * Obtain DBMS specific SQL code portion needed to set the COLLATION
     * of a field declaration to be used in statements like CREATE TABLE.
     *
     * @deprecated Deprecated since version 2.5, Use {@link self::getColumnCollationDeclarationSQL()} instead.
     *
     * @param string $collation name of the collation
     *
     * @return string  DBMS specific SQL code portion needed to set the COLLATION
     *                 of a field declaration.
     */
    public function getCollationFieldDeclaration($collation)
    {
        return $this->getColumnCollationDeclarationSQL($collation);
    }

    /**
     * MySql prefers "autoincrement" identity columns since sequences can only
     * be emulated with a table.
     */
    public function prefersIdentityColumns()
    {
        return true;
    }

    /**
     * MySql supports this through AUTO_INCREMENT columns.
     */
    public function supportsIdentityColumns()
    {
        return true;
    }

    public function supportsInlineColumnComments()
    {
        return true;
    }

    public function supportsColumnCollation()
    {
        return true;
    }

    public function getListTablesSQL()
    {
        return "SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'";
    }

    public function getListTableColumnsSQL($table, $database = null)
    {
        if ($database) {
            $database = "'" . $database . "'";
        } else {
            $database = 'DATABASE()';
        }

        return "SELECT COLUMN_NAME AS Field, COLUMN_TYPE AS Type, IS_NULLABLE AS `Null`, ".
               "COLUMN_KEY AS `Key`, COLUMN_DEFAULT AS `Default`, EXTRA AS Extra, COLUMN_COMMENT AS Comment, " .
               "CHARACTER_SET_NAME AS CharacterSet, COLLATION_NAME AS Collation ".
               "FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = " . $database . " AND TABLE_NAME = '" . $table . "'";
    }

    public function getCreateDatabaseSQL($name)
    {
        return 'CREATE DATABASE ' . $name;
    }

    public function getDropDatabaseSQL($name)
    {
        return 'DROP DATABASE ' . $name;
    }

    protected function _getCreateTableSQL($tableName, array $columns, array $options = array())
    {
        $queryFields = $this->getColumnDeclarationListSQL($columns);

        if (isset($options['uniqueConstraints']) && ! empty($options['uniqueConstraints'])) {
            foreach ($options['uniqueConstraints'] as $index => $definition) {
                $queryFields .= ', ' . $this->getUniqueConstraintDeclarationSQL($index, $definition);
            }
        }

        // add all indexes
        if (isset($options['indexes']) && ! empty($options['indexes'])) {
            foreach ($options['indexes'] as $index => $definition) {
                $queryFields .= ', ' . $this->getIndexDeclarationSQL($index, $definition);
            }
        }

        // attach all primary keys
        if (isset($options['primary']) && ! empty($options['primary'])) {
            $keyColumns = array_unique(array_values($options['primary']));
            $queryFields .= ', PRIMARY KEY(' . implode(', ', $keyColumns) . ')';
        }

        $query = 'CREATE ';

        if (!empty($options['temporary'])) {
            $query .= 'TEMPORARY ';
        }

        $query .= 'TABLE ' . $tableName . ' (' . $queryFields . ') ';
        $query .= $this->buildTableOptions($options);
        $query .= $this->buildPartitionOptions($options);

        $sql[]  = $query;
        $engine = 'INNODB';

        if (isset($options['engine'])) {
            $engine = strtoupper(trim($options['engine']));
        }

        // Propagate foreign key constraints only for InnoDB.
        if (isset($options['foreignKeys']) && $engine === 'INNODB') {
            foreach ((array) $options['foreignKeys'] as $definition) {
                $sql[] = $this->getCreateForeignKeySQL($definition, $tableName);
            }
        }

        return $sql;
    }

    public function getDefaultValueDeclarationSQL($field)
    {
        // Unset the default value if the given field definition does not allow default values.
        if ($field['type'] instanceof TextType || $field['type'] instanceof BlobType) {
            $field['default'] = null;
        }

        return parent::getDefaultValueDeclarationSQL($field);
    }

    /**
     * Build SQL for table options
     *
     * @param array $options
     *
     * @return string
     */
    private function buildTableOptions(array $options)
    {
        if (isset($options['table_options'])) {
            return $options['table_options'];
        }

        $tableOptions = array();

        // Charset
        if ( ! isset($options['charset'])) {
            $options['charset'] = 'utf8';
        }

        $tableOptions[] = sprintf('DEFAULT CHARACTER SET %s', $options['charset']);

        // Collate
        if ( ! isset($options['collate'])) {
            $options['collate'] = 'utf8_unicode_ci';
        }

        $tableOptions[] = sprintf('COLLATE %s', $options['collate']);

        // Engine
        if ( ! isset($options['engine'])) {
            $options['engine'] = 'InnoDB';
        }

        $tableOptions[] = sprintf('ENGINE = %s', $options['engine']);

        // Auto increment
        if (isset($options['auto_increment'])) {
            $tableOptions[] = sprintf('AUTO_INCREMENT = %s', $options['auto_increment']);
        }

        // Comment
        if (isset($options['comment'])) {
            $comment = trim($options['comment'], " '");

            $tableOptions[] = sprintf("COMMENT = %s ", $this->quoteStringLiteral($comment));
        }

        // Row format
        if (isset($options['row_format'])) {
            $tableOptions[] = sprintf('ROW_FORMAT = %s', $options['row_format']);
        }

        return implode(' ', $tableOptions);
    }

    /**
     * Build SQL for partition options.
     *
     * @param array $options
     *
     * @return string
     */
    private function buildPartitionOptions(array $options)
    {
        return (isset($options['partition_options']))
            ? ' ' . $options['partition_options']
            : '';
    }

    public function getAlterTableSQL(TableDiff $diff)
    {
        $columnSql = array();
        $queryParts = array();
        if ($diff->newName !== false) {
            $queryParts[] = 'RENAME TO ' . $diff->getNewName()->getQuotedName($this);
        }

        // soon 添加列 sql
        foreach ($diff->addedColumns as $column) {

            // todo: 关闭调用onSchemaAlterTableAddColumn
//            if ($this->onSchemaAlterTableAddColumn($column, $diff, $columnSql)) {
//                continue;
//            }

            $columnArray = $column->toArray();
            $columnArray['comment'] = $this->getColumnComment($column);
            $queryParts[] = 'ADD ' . $this->getColumnDeclarationSQL($column->getQuotedName($this), $columnArray);
        }

        // soon 移除列 sql
        foreach ($diff->removedColumns as $column) {

            // todo: 关闭调用onSchemaAlterTableRemoveColumn
//            if ($this->onSchemaAlterTableRemoveColumn($column, $diff, $columnSql)) {
//                continue;
//            }

            $queryParts[] =  'DROP ' . $column->getQuotedName($this);
        }

        // soon 修改列 sql
        foreach ($diff->changedColumns as $columnDiff) {

            // todo: 关闭调用onSchemaAlterTableChangeColumn
//            if ($this->onSchemaAlterTableChangeColumn($columnDiff, $diff, $columnSql)) {
//                continue;
//            }

            /* @var $columnDiff \Sojf\DBAL\Schema\ColumnDiff */
            $column = $columnDiff->column;
            $columnArray = $column->toArray();

            // Don't propagate default value changes for unsupported column types.
            if ($columnDiff->hasChanged('default') &&
                count($columnDiff->changedProperties) === 1 &&
                ($columnArray['type'] instanceof TextType || $columnArray['type'] instanceof BlobType)
            ) {
                continue;
            }

            $columnArray['comment'] = $this->getColumnComment($column);
            $queryParts[] =  'CHANGE ' . ($columnDiff->getOldColumnName()->getQuotedName($this)) . ' '
                    . $this->getColumnDeclarationSQL($column->getQuotedName($this), $columnArray);
        }

        foreach ($diff->renamedColumns as $oldColumnName => $column) {

            // todo: 关闭调用onSchemaAlterTableRenameColumn
//            if ($this->onSchemaAlterTableRenameColumn($oldColumnName, $column, $diff, $columnSql)) {
//                continue;
//            }

            $oldColumnName = new Identifier($oldColumnName);
            $columnArray = $column->toArray();
            $columnArray['comment'] = $this->getColumnComment($column);
            $queryParts[] =  'CHANGE ' . $oldColumnName->getQuotedName($this) . ' '
                    . $this->getColumnDeclarationSQL($column->getQuotedName($this), $columnArray);
        }

        if (isset($diff->addedIndexes['primary'])) {

            $keyColumns = array_unique(array_values($diff->addedIndexes['primary']->getColumns()));
            $queryParts[] = 'ADD PRIMARY KEY (' . implode(', ', $keyColumns) . ')';
            unset($diff->addedIndexes['primary']);
        }

        $sql = array();
        $tableSql = array();

        // todo: 关闭调用onSchemaAlterTable
//        if ( ! $this->onSchemaAlterTable($diff, $tableSql)) {
//            if (count($queryParts) > 0) {
//                $sql[] = 'ALTER TABLE ' . $diff->getName($this)->getQuotedName($this) . ' ' . implode(", ", $queryParts);
//            }
//            $sql = array_merge(
//                $this->getPreAlterTableIndexForeignKeySQL($diff),
//                $sql,
//                $this->getPostAlterTableIndexForeignKeySQL($diff)
//            );
//        }

        if (count($queryParts) > 0) {
            $sql[] = 'ALTER TABLE ' . $diff->getName($this)->getQuotedName($this) . ' ' . implode(", ", $queryParts);
        }

        $sql = array_merge(
            $this->getPreAlterTableIndexForeignKeySQL($diff),
            $sql,
            $this->getPostAlterTableIndexForeignKeySQL($diff)
        );

        return array_merge($sql, $tableSql, $columnSql);
    }

    protected function getPreAlterTableIndexForeignKeySQL(TableDiff $diff)
    {
        $sql = array();
        $table = $diff->getName($this)->getQuotedName($this);

        foreach ($diff->removedIndexes as $remKey => $remIndex) {
            // Dropping primary keys requires to unset autoincrement attribute on the particular column first.
            if ($remIndex->isPrimary() && $diff->fromTable instanceof Table) {
                foreach ($remIndex->getColumns() as $columnName) {
                    $column = $diff->fromTable->getColumn($columnName);

                    if ($column->getAutoincrement() === true) {
                        $column->setAutoincrement(false);

                        $sql[] = 'ALTER TABLE ' . $table . ' MODIFY ' .
                            $this->getColumnDeclarationSQL($column->getQuotedName($this), $column->toArray());

                        // original autoincrement information might be needed later on by other parts of the table alteration
                        $column->setAutoincrement(true);
                    }
                }
            }

            foreach ($diff->addedIndexes as $addKey => $addIndex) {
                if ($remIndex->getColumns() == $addIndex->getColumns()) {

                    $indexClause = 'INDEX ' . $addIndex->getName();

                    if ($addIndex->isPrimary()) {
                        $indexClause = 'PRIMARY KEY';
                    } elseif ($addIndex->isUnique()) {
                        $indexClause = 'UNIQUE INDEX ' . $addIndex->getName();
                    }

                    $query = 'ALTER TABLE ' . $table . ' DROP INDEX ' . $remIndex->getName() . ', ';
                    $query .= 'ADD ' . $indexClause;
                    $query .= ' (' . $this->getIndexFieldDeclarationListSQL($addIndex->getQuotedColumns($this)) . ')';

                    $sql[] = $query;

                    unset($diff->removedIndexes[$remKey]);
                    unset($diff->addedIndexes[$addKey]);

                    break;
                }
            }
        }

        $engine = 'INNODB';

        if ($diff->fromTable instanceof Table && $diff->fromTable->hasOption('engine')) {
            $engine = strtoupper(trim($diff->fromTable->getOption('engine')));
        }

        // Suppress foreign key constraint propagation on non-supporting engines.
        if ('INNODB' !== $engine) {
            $diff->addedForeignKeys   = array();
            $diff->changedForeignKeys = array();
            $diff->removedForeignKeys = array();
        }

        $sql = array_merge(
            $sql,
            $this->getPreAlterTableAlterIndexForeignKeySQL($diff),
            parent::getPreAlterTableIndexForeignKeySQL($diff),
            $this->getPreAlterTableRenameIndexForeignKeySQL($diff)
        );

        return $sql;
    }

    /**
     * @param TableDiff $diff The table diff to gather the SQL for.
     *
     * @return array
     */
    private function getPreAlterTableAlterIndexForeignKeySQL(TableDiff $diff)
    {
        $sql = array();
        $table = $diff->getName($this)->getQuotedName($this);

        foreach ($diff->changedIndexes as $changedIndex) {
            // Changed primary key
            if ($changedIndex->isPrimary() && $diff->fromTable instanceof Table) {
                foreach ($diff->fromTable->getPrimaryKeyColumns() as $columnName) {
                    $column = $diff->fromTable->getColumn($columnName);

                    // Check if an autoincrement column was dropped from the primary key.
                    if ($column->getAutoincrement() && ! in_array($columnName, $changedIndex->getColumns())) {
                        // The autoincrement attribute needs to be removed from the dropped column
                        // before we can drop and recreate the primary key.
                        $column->setAutoincrement(false);

                        $sql[] = 'ALTER TABLE ' . $table . ' MODIFY ' .
                            $this->getColumnDeclarationSQL($column->getQuotedName($this), $column->toArray());

                        // Restore the autoincrement attribute as it might be needed later on
                        // by other parts of the table alteration.
                        $column->setAutoincrement(true);
                    }
                }
            }
        }

        return $sql;
    }

    /**
     * @param TableDiff $diff The table diff to gather the SQL for.
     *
     * @return array
     */
    protected function getPreAlterTableRenameIndexForeignKeySQL(TableDiff $diff)
    {
        $sql = array();
        $tableName = $diff->getName($this)->getQuotedName($this);

        foreach ($this->getRemainingForeignKeyConstraintsRequiringRenamedIndexes($diff) as $foreignKey) {
            if (! in_array($foreignKey, $diff->changedForeignKeys, true)) {
                $sql[] = $this->getDropForeignKeySQL($foreignKey, $tableName);
            }
        }

        return $sql;
    }

    /**
     * Returns the remaining foreign key constraints that require one of the renamed indexes.
     *
     * "Remaining" here refers to the diff between the foreign keys currently defined in the associated
     * table and the foreign keys to be removed.
     *
     * @param TableDiff $diff The table diff to evaluate.
     *
     * @return array
     */
    private function getRemainingForeignKeyConstraintsRequiringRenamedIndexes(TableDiff $diff)
    {
        if (empty($diff->renamedIndexes) || ! $diff->fromTable instanceof Table) {
            return array();
        }

        $foreignKeys = array();

        /** @var \Sojf\DBAL\Schema\ForeignKeyConstraint[] $remainingForeignKeys */
        $remainingForeignKeys = array_diff_key(
            $diff->fromTable->getForeignKeys(),
            $diff->removedForeignKeys
        );

        foreach ($remainingForeignKeys as $foreignKey) {
            foreach ($diff->renamedIndexes as $index) {
                if ($foreignKey->intersectsIndexColumns($index)) {
                    $foreignKeys[] = $foreignKey;

                    break;
                }
            }
        }

        return $foreignKeys;
    }

    protected function getPostAlterTableIndexForeignKeySQL(TableDiff $diff)
    {
        return array_merge(
            parent::getPostAlterTableIndexForeignKeySQL($diff),
            $this->getPostAlterTableRenameIndexForeignKeySQL($diff)
        );
    }

    /**
     * @param TableDiff $diff The table diff to gather the SQL for.
     *
     * @return array
     */
    protected function getPostAlterTableRenameIndexForeignKeySQL(TableDiff $diff)
    {
        $sql = array();
        $tableName = (false !== $diff->newName)
            ? $diff->getNewName()->getQuotedName($this)
            : $diff->getName($this)->getQuotedName($this);

        foreach ($this->getRemainingForeignKeyConstraintsRequiringRenamedIndexes($diff) as $foreignKey) {
            if (! in_array($foreignKey, $diff->changedForeignKeys, true)) {
                $sql[] = $this->getCreateForeignKeySQL($foreignKey, $tableName);
            }
        }

        return $sql;
    }

    protected function getCreateIndexSQLFlags(Index $index)
    {
        $type = '';
        if ($index->isUnique()) {
            $type .= 'UNIQUE ';
        } elseif ($index->hasFlag('fulltext')) {
            $type .= 'FULLTEXT ';
        } elseif ($index->hasFlag('spatial')) {
            $type .= 'SPATIAL ';
        }

        return $type;
    }

    public function getIntegerTypeDeclarationSQL(array $field)
    {
        return 'INT' . $this->_getCommonIntegerTypeDeclarationSQL($field);
    }

    public function getBigIntTypeDeclarationSQL(array $field)
    {
        return 'BIGINT' . $this->_getCommonIntegerTypeDeclarationSQL($field);
    }

    public function getSmallIntTypeDeclarationSQL(array $field)
    {
        return 'SMALLINT' . $this->_getCommonIntegerTypeDeclarationSQL($field);
    }

    protected function _getCommonIntegerTypeDeclarationSQL(array $columnDef)
    {
        $autoinc = '';
        if ( ! empty($columnDef['autoincrement'])) {
            $autoinc = ' AUTO_INCREMENT';
        }
        $unsigned = (isset($columnDef['unsigned']) && $columnDef['unsigned']) ? ' UNSIGNED' : '';

        return $unsigned . $autoinc;
    }

    public function getAdvancedForeignKeyOptionsSQL(ForeignKeyConstraint $foreignKey)
    {
        $query = '';
        if ($foreignKey->hasOption('match')) {
            $query .= ' MATCH ' . $foreignKey->getOption('match');
        }
        $query .= parent::getAdvancedForeignKeyOptionsSQL($foreignKey);

        return $query;
    }

    public function getDropIndexSQL($index, $table=null)
    {
        if ($index instanceof Index) {
            $indexName = $index->getQuotedName($this);
        } elseif (is_string($index)) {
            $indexName = $index;
        } else {
            throw new \InvalidArgumentException('MysqlPlatform::getDropIndexSQL() expects $index parameter to be string or \Doctrine\DBAL\Schema\Index.');
        }

        if ($table instanceof Table) {
            $table = $table->getQuotedName($this);
        } elseif (!is_string($table)) {
            throw new \InvalidArgumentException('MysqlPlatform::getDropIndexSQL() expects $table parameter to be string or \Doctrine\DBAL\Schema\Table.');
        }

        if ($index instanceof Index && $index->isPrimary()) {
            // mysql primary keys are always named "PRIMARY",
            // so we cannot use them in statements because of them being keyword.
            return $this->getDropPrimaryKeySQL($table);
        }

        return 'DROP INDEX ' . $indexName . ' ON ' . $table;
    }

    /**
     * @param string $table
     *
     * @return string
     */
    protected function getDropPrimaryKeySQL($table)
    {
        return 'ALTER TABLE ' . $table . ' DROP PRIMARY KEY';
    }

    public function getSetTransactionIsolationSQL($level)
    {
        return 'SET SESSION TRANSACTION ISOLATION LEVEL ' . $this->_getTransactionIsolationLevelSQL($level);
    }

    public function getName()
    {
        return 'mysql';
    }

    public function getReadLockSQL()
    {
        return 'LOCK IN SHARE MODE';
    }

    public function getVarcharMaxLength()
    {
        return 65535;
    }

    public function getBinaryMaxLength()
    {
        return 65535;
    }

    protected function getReservedKeywordsClass()
    {
        return 'Sojf\DBAL\Platforms\Keywords\MySQLKeywords';
    }

    /**
     * {@inheritDoc}
     *
     * MySQL commits a transaction implicitly when DROP TABLE is executed, however not
     * if DROP TEMPORARY TABLE is executed.
     */
    public function getDropTemporaryTableSQL($table)
    {
        if ($table instanceof Table) {
            $table = $table->getQuotedName($this);
        } elseif (!is_string($table)) {
            throw new \InvalidArgumentException('getDropTableSQL() expects $table parameter to be string or \Doctrine\DBAL\Schema\Table.');
        }

        return 'DROP TEMPORARY TABLE ' . $table;
    }

    /**
     * Gets the SQL Snippet used to declare a BLOB column type.
     *     TINYBLOB   : 2 ^  8 - 1 = 255
     *     BLOB       : 2 ^ 16 - 1 = 65535
     *     MEDIUMBLOB : 2 ^ 24 - 1 = 16777215
     *     LONGBLOB   : 2 ^ 32 - 1 = 4294967295
     *
     * @param array $field
     *
     * @return string
     */
    public function getBlobTypeDeclarationSQL(array $field)
    {
        if ( ! empty($field['length']) && is_numeric($field['length'])) {
            $length = $field['length'];

            if ($length <= static::LENGTH_LIMIT_TINYBLOB) {
                return 'TINYBLOB';
            }

            if ($length <= static::LENGTH_LIMIT_BLOB) {
                return 'BLOB';
            }

            if ($length <= static::LENGTH_LIMIT_MEDIUMBLOB) {
                return 'MEDIUMBLOB';
            }
        }

        return 'LONGBLOB';
    }
}
