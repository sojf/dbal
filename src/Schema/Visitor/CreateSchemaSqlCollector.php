<?php
namespace Sojf\DBAL\Schema\Visitor;

use Sojf\DBAL\Abstracts\Platform;

use Sojf\DBAL\Schema\Table;
use Sojf\DBAL\Schema\ForeignKeyConstraint;
use Sojf\DBAL\Schema\Sequence;

class CreateSchemaSqlCollector extends AbstractVisitor
{
    /**
     * @var array
     */
    private $createNamespaceQueries = array();

    /**
     * @var array
     */
    private $createTableQueries = array();

    /**
     * @var array
     */
    private $createSequenceQueries = array();

    /**
     * @var array
     */
    private $createFkConstraintQueries = array();

    /**
     *
     * @var \Sojf\DBAL\Abstracts\Platform
     */
    private $platform = null;

    /**
     * @param Platform $platform
     */
    public function __construct(Platform $platform)
    {
        $this->platform = $platform;
    }

    /**
     * {@inheritdoc}
     */
    public function acceptNamespace($namespaceName)
    {
        if ($this->platform->supportsSchemas()) {
            $this->createNamespaceQueries = array_merge(
                $this->createNamespaceQueries,
                (array) $this->platform->getCreateSchemaSQL($namespaceName)
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function acceptTable(Table $table)
    {
        $this->createTableQueries = array_merge($this->createTableQueries, (array) $this->platform->getCreateTableSQL($table));
    }

    /**
     * {@inheritdoc}
     */
    public function acceptForeignKey(Table $localTable, ForeignKeyConstraint $fkConstraint)
    {
        if ($this->platform->supportsForeignKeyConstraints()) {
            $this->createFkConstraintQueries = array_merge(
                $this->createFkConstraintQueries,
                (array) $this->platform->getCreateForeignKeySQL(
                    $fkConstraint, $localTable
                )
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function acceptSequence(Sequence $sequence)
    {
        $this->createSequenceQueries = array_merge(
            $this->createSequenceQueries,
            (array) $this->platform->getCreateSequenceSQL($sequence)
        );
    }

    /**
     * @return void
     */
    public function resetQueries()
    {
        $this->createNamespaceQueries = array();
        $this->createTableQueries = array();
        $this->createSequenceQueries = array();
        $this->createFkConstraintQueries = array();
    }

    /**
     * Gets all queries collected so far.
     *
     * @return array
     */
    public function getQueries()
    {
        $sql = array();

        foreach ($this->createNamespaceQueries as $schemaSql) {
            $sql = array_merge($sql, (array) $schemaSql);
        }

        foreach ($this->createTableQueries as $schemaSql) {
            $sql = array_merge($sql, (array) $schemaSql);
        }

        foreach ($this->createSequenceQueries as $schemaSql) {
            $sql = array_merge($sql, (array) $schemaSql);
        }

        foreach ($this->createFkConstraintQueries as $schemaSql) {
            $sql = array_merge($sql, (array) $schemaSql);
        }

        return $sql;
    }
}
