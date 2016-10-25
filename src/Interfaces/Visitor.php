<?php
namespace Sojf\DBAL\Interfaces;

use Sojf\DBAL\Schema\Table;
use Sojf\DBAL\Schema\Schema;
use Sojf\DBAL\Schema\Column;
use Sojf\DBAL\Schema\ForeignKeyConstraint;
use Sojf\DBAL\Schema\Sequence;
use Sojf\DBAL\Schema\Index;

/**
 * Schema Visitor used for Validation or Generation purposes.
 *
 * @link   www.doctrine-project.org
 * @since  2.0
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
interface Visitor
{
    /**
     * @param \Sojf\DBAL\Schema\Schema $schema
     *
     * @return void
     */
    public function acceptSchema(Schema $schema);

    /**
     * @param \Sojf\DBAL\Schema\Table $table
     *
     * @return void
     */
    public function acceptTable(Table $table);

    /**
     * @param \Sojf\DBAL\Schema\Table  $table
     * @param \Sojf\DBAL\Schema\Column $column
     *
     * @return void
     */
    public function acceptColumn(Table $table, Column $column);

    /**
     * @param \Sojf\DBAL\Schema\Table                $localTable
     * @param \Sojf\DBAL\Schema\ForeignKeyConstraint $fkConstraint
     *
     * @return void
     */
    public function acceptForeignKey(Table $localTable, ForeignKeyConstraint $fkConstraint);

    /**
     * @param \Sojf\DBAL\Schema\Table $table
     * @param \Sojf\DBAL\Schema\Index $index
     *
     * @return void
     */
    public function acceptIndex(Table $table, Index $index);

    /**
     * @param \Sojf\DBAL\Schema\Sequence $sequence
     *
     * @return void
     */
    public function acceptSequence(Sequence $sequence);
}
