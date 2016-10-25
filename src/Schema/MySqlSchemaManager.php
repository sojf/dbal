<?php
namespace Sojf\DBAL\Schema;

use Sojf\DBAL\Platforms\MySql;
use Sojf\DBAL\Abstracts\Type;
use Sojf\DBAL\Abstracts\SchemaManager;


class MySqlSchemaManager extends SchemaManager
{
    // todo 栏位解析
    protected function _getPortableTableColumnDefinition($tableColumn)
    {
        $tableColumn = array_change_key_case($tableColumn, CASE_LOWER);

        $dbType = strtolower($tableColumn['type']);
        $dbType = strtok($dbType, '(), ');

        $list = array();
        if ($dbType === 'enum' || $dbType === 'set') {

            preg_match('#\((.+)\)#', $tableColumn['type'], $matches);
            $list = str_replace("'", '', $matches[1]);
            $list = explode(',', $list);

            if (isset($tableColumn['length'])) {
                $length = $tableColumn['length'];
            } else {
                $length = count(explode(',', $tableColumn['type']));
            }

        } else {

            if (isset($tableColumn['length'])) {
                $length = $tableColumn['length'];
            } else {
                $length = strtok('(), ');
            }
        }

        $fixed = null;

        if ( ! isset($tableColumn['name'])) {
            $tableColumn['name'] = '';
        }

        $scale = null;
        $precision = null;

        // todo 调用平台方法 获取类型对象
        $type = $this->_platform->getDoctrineTypeMapping($dbType);

        // In cases where not connected to a database DESCRIBE $table does not return 'Comment'
        if (isset($tableColumn['comment'])) {
            $type = $this->extractDoctrineTypeFromComment($tableColumn['comment'], $type);
            $tableColumn['comment'] = $this->removeDoctrineTypeFromComment($tableColumn['comment'], $type);
        }

        switch ($dbType) {
            case 'char':
            case 'binary':
                $fixed = true;
                break;
            case 'float':
            case 'double':
            case 'real':
            case 'numeric':
            case 'decimal':
                if (preg_match('([A-Za-z]+\(([0-9]+)\,([0-9]+)\))', $tableColumn['type'], $match)) {
                    $precision = $match[1];
                    $scale = $match[2];
                    $length = null;
                }
                break;
            case 'tinytext':
                $length = MySql::LENGTH_LIMIT_TINYTEXT;
                break;
            case 'text':
                $length = MySql::LENGTH_LIMIT_TEXT;
                break;
            case 'mediumtext':
                $length = MySql::LENGTH_LIMIT_MEDIUMTEXT;
                break;
            case 'tinyblob':
                $length = MySql::LENGTH_LIMIT_TINYBLOB;
                break;
            case 'blob':
                $length = MySql::LENGTH_LIMIT_BLOB;
                break;
            case 'mediumblob':
                $length = MySql::LENGTH_LIMIT_MEDIUMBLOB;
                break;
            case 'tinyint':
            case 'smallint':
            case 'mediumint':
            case 'int':
            case 'integer':
            case 'bigint':
            case 'year':
                $length = null;
                break;
        }

        $length = ((int) $length == 0) ? null : (int) $length;

        // todo 栏位属性
        $options = array(
            'length'        => $length,
            'list'          => array(),
            'unsigned'      => (bool) (strpos($tableColumn['type'], 'unsigned') !== false),
            'fixed'         => (bool) $fixed,
            'default'       => isset($tableColumn['default']) ? $tableColumn['default'] : null,
            'notnull'       => (bool) ($tableColumn['null'] != 'YES'),
            'scale'         => null,
            'precision'     => null,
            'autoincrement' => (bool) (strpos($tableColumn['extra'], 'auto_increment') !== false),
            'comment'       => isset($tableColumn['comment']) && $tableColumn['comment'] !== ''
                ? $tableColumn['comment']
                : null,
        );

        if ($scale !== null && $precision !== null) {
            $options['scale'] = $scale;
            $options['precision'] = $precision;
        }

        if ($list) {
            $options['list'] = $list;
        }

        // todo 实例化栏位对象
        $column = new Column($tableColumn['field'], Type::getType($type), $options);

        if (isset($tableColumn['collation'])) {
            $column->setPlatformOption('collation', $tableColumn['collation']);
        }

        return $column;
    }

    protected function _getPortableViewDefinition($view)
    {
        return new View($view['TABLE_NAME'], $view['VIEW_DEFINITION']);
    }

    protected function _getPortableTableDefinition($table)
    {
        return array_shift($table);
    }

    protected function _getPortableUserDefinition($user)
    {
        return array(
            'user' => $user['User'],
            'password' => $user['Password'],
        );
    }

    // todo 覆盖父类方法
    protected function _getPortableTableIndexesList($tableIndexes, $tableName=null)
    {
        $indexes = array();
        $result = array();

        foreach ($tableIndexes as $k => $v) {

            $v = array_change_key_case($v, CASE_LOWER);

            if ($v['key_name'] == 'PRIMARY') {

                $v['primary'] = true;
            } else {

                $v['primary'] = false;
            }

            if (strpos($v['index_type'], 'FULLTEXT') !== false) {

                $v['flags'] = array('FULLTEXT');
            } elseif (strpos($v['index_type'], 'SPATIAL') !== false) {

                $v['flags'] = array('SPATIAL');
            }

            // 父类方法

            $indexName = $keyName = $v['key_name'];

            if ($v['primary']) {
                $keyName = 'primary';
            }

            $keyName = strtolower($keyName);

            if (!isset($result[$keyName])) {

                $result = array(
                    'name' => $indexName,
                    'columns' => array($v['column_name']),
                    'unique' => $v['non_unique'] ? false : true,
                    'primary' => $v['primary'],
                    'flags' => isset($v['flags']) ? $v['flags'] : array(),
                    'options' => isset($v['where']) ? array('where' => $v['where']) : array(),
                );
            } else {

                $result['columns'][] = $v['column_name'];
            }

//            $tableIndexes[$k] = $v;

            $index = new Index($result['name'], $result['columns'], $result['unique'], $result['primary'], $result['flags'], $result['options']);

            $indexes[$keyName] = $index;
        }

        return $indexes;

//        return parent::_getPortableTableIndexesList($tableIndexes, $tableName);
    }

    protected function _getPortableSequenceDefinition($sequence)
    {
        return end($sequence);
    }

    protected function _getPortableDatabaseDefinition($database)
    {
        return $database['Database'];
    }

    protected function _getPortableTableForeignKeysList($tableForeignKeys)
    {
        $list = array();
        foreach ($tableForeignKeys as $value) {
            $value = array_change_key_case($value, CASE_LOWER);
            if (!isset($list[$value['constraint_name']])) {
                if (!isset($value['delete_rule']) || $value['delete_rule'] == "RESTRICT") {
                    $value['delete_rule'] = null;
                }
                if (!isset($value['update_rule']) || $value['update_rule'] == "RESTRICT") {
                    $value['update_rule'] = null;
                }

                $list[$value['constraint_name']] = array(
                    'name' => $value['constraint_name'],
                    'local' => array(),
                    'foreign' => array(),
                    'foreignTable' => $value['referenced_table_name'],
                    'onDelete' => $value['delete_rule'],
                    'onUpdate' => $value['update_rule'],
                );
            }
            $list[$value['constraint_name']]['local'][] = $value['column_name'];
            $list[$value['constraint_name']]['foreign'][] = $value['referenced_column_name'];
        }

        $result = array();
        foreach ($list as $constraint) {
            $result[] = new ForeignKeyConstraint(
                array_values($constraint['local']), $constraint['foreignTable'],
                array_values($constraint['foreign']), $constraint['name'],
                array(
                    'onDelete' => $constraint['onDelete'],
                    'onUpdate' => $constraint['onUpdate'],
                )
            );
        }

        return $result;
    }
}
