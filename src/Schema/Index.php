<?php
namespace Sojf\DBAL\Schema;


use Sojf\DBAL\Interfaces\Constraint;
use Sojf\DBAL\Abstracts\Platform;
use Sojf\DBAL\Abstracts\Asset;

class Index extends Asset implements Constraint
{
    /**
     * Asset identifier instances of the column names the index is associated with.
     * array($columnName => Identifier)
     *
     * @var Identifier[]
     */
    protected $_columns = array();

    /**
     * @var boolean
     */
    protected $_isUnique = false;

    /**
     * @var boolean
     */
    protected $_isPrimary = false;

    /**
     * Platform specific flags for indexes.
     * array($flagName => true)
     *
     * @var array
     */
    protected $_flags = array();

    /**
     * Platform specific options
     *
     * @todo $_flags should eventually be refactored into options
     *
     * @var array
     */
    private $options = array();

    /**
     * @param string   $indexName
     * @param string[] $columns
     * @param boolean  $isUnique
     * @param boolean  $isPrimary
     * @param string[] $flags
     * @param array    $options
     */
    public function __construct($indexName, array $columns, $isUnique = false, $isPrimary = false, array $flags = array(), array $options = array())
    {
        $isUnique = $isUnique || $isPrimary;

        $this->_setName($indexName);
        $this->_isUnique = $isUnique;
        $this->_isPrimary = $isPrimary;
        $this->options = $options;

        foreach ($columns as $column) {
            $this->_addColumn($column);
        }
        foreach ($flags as $flag) {
            $this->addFlag($flag);
        }
    }

    /**
     * @param string $column
     *
     * @return void
     *
     * @throws \InvalidArgumentException
     */
    protected function _addColumn($column)
    {
        if (is_string($column)) {
            $this->_columns[$column] = new Identifier($column);
        } else {
            throw new \InvalidArgumentException("Expecting a string as Index Column");
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getColumns()
    {
        return array_keys($this->_columns);
    }

    /**
     * {@inheritdoc}
     */
    public function getQuotedColumns(Platform $platform)
    {
        $columns = array();

        foreach ($this->_columns as $column) {
            $columns[] = $column->getQuotedName($platform);
        }

        return $columns;
    }

    /**
     * @return string[]
     */
    public function getUnquotedColumns()
    {
        return array_map(array($this, 'trimQuotes'), $this->getColumns());
    }

    /**
     * Is the index neither unique nor primary key?
     *
     * @return boolean
     */
    public function isSimpleIndex()
    {
        return !$this->_isPrimary && !$this->_isUnique;
    }

    /**
     * @return boolean
     */
    public function isUnique()
    {
        return $this->_isUnique;
    }

    /**
     * @return boolean
     */
    public function isPrimary()
    {
        return $this->_isPrimary;
    }

    /**
     * @param string  $columnName
     * @param integer $pos
     *
     * @return boolean
     */
    public function hasColumnAtPosition($columnName, $pos = 0)
    {
        $columnName   = $this->trimQuotes(strtolower($columnName));
        $indexColumns = array_map('strtolower', $this->getUnquotedColumns());

        return array_search($columnName, $indexColumns) === $pos;
    }

    /**
     * Checks if this index exactly spans the given column names in the correct order.
     *
     * @param array $columnNames
     *
     * @return boolean
     */
    public function spansColumns(array $columnNames)
    {
        $columns         = $this->getColumns();
        $numberOfColumns = count($columns);
        $sameColumns     = true;

        for ($i = 0; $i < $numberOfColumns; $i++) {
            if ( ! isset($columnNames[$i]) || $this->trimQuotes(strtolower($columns[$i])) !== $this->trimQuotes(strtolower($columnNames[$i]))) {
                $sameColumns = false;
            }
        }

        return $sameColumns;
    }

    /**
     * Checks if the other index already fulfills all the indexing and constraint needs of the current one.
     *
     * @param Index $other
     *
     * @return boolean
     */
    public function isFullfilledBy(Index $other)
    {
        // allow the other index to be equally large only. It being larger is an option
        // but it creates a problem with scenarios of the kind PRIMARY KEY(foo,bar) UNIQUE(foo)
        if (count($other->getColumns()) != count($this->getColumns())) {
            return false;
        }

        // Check if columns are the same, and even in the same order
        $sameColumns = $this->spansColumns($other->getColumns());

        if ($sameColumns) {
            if ( ! $this->samePartialIndex($other)) {
                return false;
            }

            if ( ! $this->isUnique() && ! $this->isPrimary()) {
                // this is a special case: If the current key is neither primary or unique, any uniqe or
                // primary key will always have the same effect for the index and there cannot be any constraint
                // overlaps. This means a primary or unique index can always fulfill the requirements of just an
                // index that has no constraints.
                return true;
            }

            if ($other->isPrimary() != $this->isPrimary()) {
                return false;
            }

            if ($other->isUnique() != $this->isUnique()) {
                return false;
            }

            return true;
        }

        return false;
    }

    /**
     * Detects if the other index is a non-unique, non primary index that can be overwritten by this one.
     *
     * @param Index $other
     *
     * @return boolean
     */
    public function overrules(Index $other)
    {
        if ($other->isPrimary()) {
            return false;
        } elseif ($this->isSimpleIndex() && $other->isUnique()) {
            return false;
        }

        if ($this->spansColumns($other->getColumns()) && ($this->isPrimary() || $this->isUnique()) && $this->samePartialIndex($other)) {
            return true;
        }

        return false;
    }

    /**
     * Returns platform specific flags for indexes.
     *
     * @return string[]
     */
    public function getFlags()
    {
        return array_keys($this->_flags);
    }

    /**
     * Adds Flag for an index that translates to platform specific handling.
     *
     * @example $index->addFlag('CLUSTERED')
     *
     * @param string $flag
     *
     * @return Index
     */
    public function addFlag($flag)
    {
        $this->_flags[strtolower($flag)] = true;

        return $this;
    }

    /**
     * Does this index have a specific flag?
     *
     * @param string $flag
     *
     * @return boolean
     */
    public function hasFlag($flag)
    {
        return isset($this->_flags[strtolower($flag)]);
    }

    /**
     * Removes a flag.
     *
     * @param string $flag
     *
     * @return void
     */
    public function removeFlag($flag)
    {
        unset($this->_flags[strtolower($flag)]);
    }

    /**
     * @param string $name
     *
     * @return boolean
     */
    public function hasOption($name)
    {
        return isset($this->options[strtolower($name)]);
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function getOption($name)
    {
        return $this->options[strtolower($name)];
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Return whether the two indexes have the same partial index
     * @param \Sojf\DBAL\Schema\Index $other
     *
     * @return boolean
     */
    private function samePartialIndex(Index $other)
    {
        if ($this->hasOption('where') && $other->hasOption('where') && $this->getOption('where') == $other->getOption('where')) {
            return true;
        }

        if ( ! $this->hasOption('where') && ! $other->hasOption('where')) {
            return true;
        }

        return false;
    }

}
