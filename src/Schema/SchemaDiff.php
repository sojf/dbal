<?php
namespace Sojf\DBAL\Schema;


use Sojf\DBAL\Abstracts\Platform;

/**
 * Schema Diff.
 *
 * @link      www.doctrine-project.org
 * @copyright Copyright (C) 2005-2009 eZ Systems AS. All rights reserved.
 * @license   http://ez.no/licenses/new_bsd New BSD License
 * @since     2.0
 * @author    Benjamin Eberlei <kontakt@beberlei.de>
 */
class SchemaDiff
{
    /**
     * @var \Sojf\DBAL\Schema\Schema
     */
    public $fromSchema;

    /**
     * All added namespaces.
     *
     * @var string[]
     */
    public $newNamespaces = array();

    /**
     * All removed namespaces.
     *
     * @var string[]
     */
    public $removedNamespaces = array();

    /**
     * All added tables.
     *
     * @var \Sojf\DBAL\Schema\Table[]
     */
    public $newTables = array();

    /**
     * All changed tables.
     *
     * @var \Sojf\DBAL\Schema\TableDiff[]
     */
    public $changedTables = array();

    /**
     * All removed tables.
     *
     * @var \Sojf\DBAL\Schema\Table[]
     */
    public $removedTables = array();

    /**
     * @var \Sojf\DBAL\Schema\Sequence[]
     */
    public $newSequences = array();

    /**
     * @var \Sojf\DBAL\Schema\Sequence[]
     */
    public $changedSequences = array();

    /**
     * @var \Sojf\DBAL\Schema\Sequence[]
     */
    public $removedSequences = array();

    /**
     * @var \Sojf\DBAL\Schema\ForeignKeyConstraint[]
     */
    public $orphanedForeignKeys = array();

    /**
     * Constructs an SchemaDiff object.
     *
     * @param \Sojf\DBAL\Schema\Table[]     $newTables
     * @param \Sojf\DBAL\Schema\TableDiff[] $changedTables
     * @param \Sojf\DBAL\Schema\Table[]     $removedTables
     * @param \Sojf\DBAL\Schema\Schema|null $fromSchema
     */
    public function __construct($newTables = array(), $changedTables = array(), $removedTables = array(), Schema $fromSchema = null)
    {
        $this->newTables     = $newTables;
        $this->changedTables = $changedTables;
        $this->removedTables = $removedTables;
        $this->fromSchema    = $fromSchema;
    }

    /**
     * The to save sql mode ensures that the following things don't happen:
     *
     * 1. Tables are deleted
     * 2. Sequences are deleted
     * 3. Foreign Keys which reference tables that would otherwise be deleted.
     *
     * This way it is ensured that assets are deleted which might not be relevant to the metadata schema at all.
     *
     * @param Platform $platform
     *
     * @return array
     */
    public function toSaveSql(Platform $platform)
    {
        return $this->_toSql($platform, true);
    }

    /**
     * @param Platform $platform
     *
     * @return array
     */
    public function toSql(Platform $platform)
    {
        return $this->_toSql($platform, false);
    }

    /**
     * @param Platform $platform
     * @param boolean                                   $saveMode
     *
     * @return array
     */
    protected function _toSql(Platform $platform, $saveMode = false)
    {
        $sql = array();

        if ($platform->supportsSchemas()) {
            foreach ($this->newNamespaces as $newNamespace) {
                $sql[] = $platform->getCreateSchemaSQL($newNamespace);
            }
        }

        if ($platform->supportsForeignKeyConstraints() && $saveMode == false) {
            foreach ($this->orphanedForeignKeys as $orphanedForeignKey) {
                $sql[] = $platform->getDropForeignKeySQL($orphanedForeignKey, $orphanedForeignKey->getLocalTableName());
            }
        }

        if ($platform->supportsSequences() == true) {
            foreach ($this->changedSequences as $sequence) {
                $sql[] = $platform->getAlterSequenceSQL($sequence);
            }

            if ($saveMode === false) {
                foreach ($this->removedSequences as $sequence) {
                    $sql[] = $platform->getDropSequenceSQL($sequence);
                }
            }

            foreach ($this->newSequences as $sequence) {
                $sql[] = $platform->getCreateSequenceSQL($sequence);
            }
        }

        $foreignKeySql = array();
        foreach ($this->newTables as $table) {
            $sql = array_merge(
                $sql,
                $platform->getCreateTableSQL($table, Platform::CREATE_INDEXES)
            );

            if ($platform->supportsForeignKeyConstraints()) {
                foreach ($table->getForeignKeys() as $foreignKey) {
                    $foreignKeySql[] = $platform->getCreateForeignKeySQL($foreignKey, $table);
                }
            }
        }

        $sql = array_merge($sql, $foreignKeySql);
        if ($saveMode === false) {
            foreach ($this->removedTables as $table) {
                $sql[] = $platform->getDropTableSQL($table);
            }
        }

        foreach ($this->changedTables as $tableDiff) {
            $sql = array_merge($sql, $platform->getAlterTableSQL($tableDiff));
        }

        return $sql;
    }
}
