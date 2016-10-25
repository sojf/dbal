<?php
namespace Sojf\DBAL\Schema\Visitor;


use Sojf\DBAL\Interfaces\Visitor;
use Sojf\DBAL\Interfaces\NamespaceVisitor;

use Sojf\DBAL\Schema\Table;
use Sojf\DBAL\Schema\Schema;
use Sojf\DBAL\Schema\Column;
use Sojf\DBAL\Schema\ForeignKeyConstraint;
use Sojf\DBAL\Schema\Sequence;
use Sojf\DBAL\Schema\Index;

/**
 * Abstract Visitor with empty methods for easy extension.
 */
class AbstractVisitor implements Visitor, NamespaceVisitor
{
    /**
     * @param \Sojf\DBAL\Schema\Schema $schema
     */
    public function acceptSchema(Schema $schema)
    {
    }

    /**
     * {@inheritdoc}
     */
    public function acceptNamespace($namespaceName)
    {
    }

    /**
     * @param \Sojf\DBAL\Schema\Table $table
     */
    public function acceptTable(Table $table)
    {
    }

    /**
     * @param \Sojf\DBAL\Schema\Table  $table
     * @param \Sojf\DBAL\Schema\Column $column
     */
    public function acceptColumn(Table $table, Column $column)
    {
    }

    /**
     * @param \Sojf\DBAL\Schema\Table                $localTable
     * @param \Sojf\DBAL\Schema\ForeignKeyConstraint $fkConstraint
     */
    public function acceptForeignKey(Table $localTable, ForeignKeyConstraint $fkConstraint)
    {
    }

    /**
     * @param \Sojf\DBAL\Schema\Table $table
     * @param \Sojf\DBAL\Schema\Index $index
     */
    public function acceptIndex(Table $table, Index $index)
    {
    }

    /**
     * @param \Sojf\DBAL\Schema\Sequence $sequence
     */
    public function acceptSequence(Sequence $sequence)
    {
    }
}
