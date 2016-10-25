<?php
namespace Sojf\DBAL\Platforms;

use Sojf\DBAL\Schema\Index;
use Sojf\DBAL\Schema\TableDiff;

/**
 * Provides the behavior, features and SQL dialect of the MySQL 5.7 database platform.
 *
 * @author Steve Müller <st.mueller@dzh-online.de>
 * @link   www.doctrine-project.org
 * @since  2.5
 */
class MySQL57 extends MySql
{
    protected function getPreAlterTableRenameIndexForeignKeySQL(TableDiff $diff)
    {
        return array();
    }

    protected function getPostAlterTableRenameIndexForeignKeySQL(TableDiff $diff)
    {
        return array();
    }

    protected function getRenameIndexSQL($oldIndexName, Index $index, $tableName)
    {
        return array(
            'ALTER TABLE ' . $tableName . ' RENAME INDEX ' . $oldIndexName . ' TO ' . $index->getQuotedName($this)
        );
    }

    protected function getReservedKeywordsClass()
    {
        return 'Doctrine\DBAL\Platforms\Keywords\MySQL57Keywords';
    }
}
