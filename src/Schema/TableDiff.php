<?php
namespace Sojf\DBAL\Schema;

use Sojf\DBAL\Abstracts\Platform;

/**
 * Table Diff.
 *
 * @link   www.doctrine-project.org
 * @since  2.0
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
class TableDiff
{
    /**
     * @var string
     */
    public $name = null;

    /**
     * @var string|boolean
     */
    public $newName = false;

    /**
     * All added fields.
     *
     * @var \Sojf\DBAL\Schema\Column[]
     */
    public $addedColumns;

    /**
     * All changed fields.
     *
     * @var \Sojf\DBAL\Schema\ColumnDiff[]
     */
    public $changedColumns = array();

    /**
     * All removed fields.
     *
     * @var \Sojf\DBAL\Schema\Column[]
     */
    public $removedColumns = array();

    /**
     * Columns that are only renamed from key to column instance name.
     *
     * @var \Sojf\DBAL\Schema\Column[]
     */
    public $renamedColumns = array();

    /**
     * All added indexes.
     *
     * @var \Sojf\DBAL\Schema\Index[]
     */
    public $addedIndexes = array();

    /**
     * All changed indexes.
     *
     * @var \Sojf\DBAL\Schema\Index[]
     */
    public $changedIndexes = array();

    /**
     * All removed indexes
     *
     * @var \Sojf\DBAL\Schema\Index[]
     */
    public $removedIndexes = array();

    /**
     * Indexes that are only renamed but are identical otherwise.
     *
     * @var \Sojf\DBAL\Schema\Index[]
     */
    public $renamedIndexes = array();

    /**
     * All added foreign key definitions
     *
     * @var \Sojf\DBAL\Schema\ForeignKeyConstraint[]
     */
    public $addedForeignKeys = array();

    /**
     * All changed foreign keys
     *
     * @var \Sojf\DBAL\Schema\ForeignKeyConstraint[]
     */
    public $changedForeignKeys = array();

    /**
     * All removed foreign keys
     *
     * @var \Sojf\DBAL\Schema\ForeignKeyConstraint[]
     */
    public $removedForeignKeys = array();

    /**
     * @var \Sojf\DBAL\Schema\Table
     */
    public $fromTable;

    /**
     * Constructs an TableDiff object.
     *
     * @param string                             $tableName
     * @param \Sojf\DBAL\Schema\Column[]     $addedColumns
     * @param \Sojf\DBAL\Schema\ColumnDiff[] $changedColumns
     * @param \Sojf\DBAL\Schema\Column[]     $removedColumns
     * @param \Sojf\DBAL\Schema\Index[]      $addedIndexes
     * @param \Sojf\DBAL\Schema\Index[]      $changedIndexes
     * @param \Sojf\DBAL\Schema\Index[]      $removedIndexes
     * @param \Sojf\DBAL\Schema\Table|null   $fromTable
     */
    public function __construct($tableName, $addedColumns = array(),
        $changedColumns = array(), $removedColumns = array(), $addedIndexes = array(),
        $changedIndexes = array(), $removedIndexes = array(), Table $fromTable = null)
    {
        $this->name = $tableName;
        $this->addedColumns = $addedColumns;
        $this->changedColumns = $changedColumns;
        $this->removedColumns = $removedColumns;
        $this->addedIndexes = $addedIndexes;
        $this->changedIndexes = $changedIndexes;
        $this->removedIndexes = $removedIndexes;
        $this->fromTable = $fromTable;
    }

    /**
     * @param Platform $platform The platform to use for retrieving this table diff's name.
     *
     * @return \Sojf\DBAL\Schema\Identifier
     */
    public function getName(Platform $platform)
    {
        return new Identifier(
            $this->fromTable instanceof Table ? $this->fromTable->getQuotedName($platform) : $this->name
        );
    }

    /**
     * @return \Sojf\DBAL\Schema\Identifier|boolean
     */
    public function getNewName()
    {
        return $this->newName ? new Identifier($this->newName) : $this->newName;
    }
}
