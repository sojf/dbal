<?php
namespace Sojf\DBAL\Schema\Visitor;

use Sojf\DBAL\Abstracts\Platform;
use Sojf\DBAL\Schema\Table;
use Sojf\DBAL\Schema\ForeignKeyConstraint;
use Sojf\DBAL\Schema\Sequence;
use Sojf\DBAL\Exceptions\Schema as SchemaException;

/**
 * Gathers SQL statements that allow to completely drop the current schema.
 *
 * @link   www.doctrine-project.org
 * @since  2.0
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 */
class DropSchemaSqlCollector extends AbstractVisitor
{
    /**
     * @var \SplObjectStorage
     */
    private $constraints;

    /**
     * @var \SplObjectStorage
     */
    private $sequences;

    /**
     * @var \SplObjectStorage
     */
    private $tables;

    /**
     * @var Platform
     */
    private $platform;

    /**
     * @param Platform $platform
     */
    public function __construct(Platform $platform)
    {
        $this->platform = $platform;
        $this->clearQueries();
    }

    /**
     * {@inheritdoc}
     */
    public function acceptTable(Table $table)
    {
        $this->tables->attach($table);
    }

    /**
     * {@inheritdoc}
     */
    public function acceptForeignKey(Table $localTable, ForeignKeyConstraint $fkConstraint)
    {
        if (strlen($fkConstraint->getName()) == 0) {
            throw SchemaException::namedForeignKeyRequired($localTable, $fkConstraint);
        }

        $this->constraints->attach($fkConstraint, $localTable);
    }

    /**
     * {@inheritdoc}
     */
    public function acceptSequence(Sequence $sequence)
    {
        $this->sequences->attach($sequence);
    }

    /**
     * @return void
     */
    public function clearQueries()
    {
        $this->constraints = new \SplObjectStorage();
        $this->sequences = new \SplObjectStorage();
        $this->tables = new \SplObjectStorage();
    }

    /**
     * @return array
     */
    public function getQueries()
    {
        $sql = array();

        foreach ($this->constraints as $fkConstraint) {
            $localTable = $this->constraints[$fkConstraint];
            $sql[] = $this->platform->getDropForeignKeySQL($fkConstraint, $localTable);
        }

        foreach ($this->sequences as $sequence) {
            $sql[] = $this->platform->getDropSequenceSQL($sequence);
        }

        foreach ($this->tables as $table) {
            $sql[] = $this->platform->getDropTableSQL($table);
        }

        return $sql;
    }
}
