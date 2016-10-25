<?php
namespace Sojf\DBAL\Query;

use Sojf\DBAL\Exceptions\DBAL as DBALException;

/**
 * @since 2.1.4
 */
class QueryException extends DBALException
{
    /**
     * @param string $alias
     * @param array  $registeredAliases
     *
     * @return \Sojf\DBAL\Query\QueryException
     */
    static public function unknownAlias($alias, $registeredAliases)
    {
        return new self("The given alias '" . $alias . "' is not part of " .
            "any FROM or JOIN clause table. The currently registered " .
            "aliases are: " . implode(", ", $registeredAliases) . ".");
    }

    /**
     * @param string $alias
     * @param array  $registeredAliases
     *
     * @return \Sojf\DBAL\Query\QueryException
     */
    static public function nonUniqueAlias($alias, $registeredAliases)
    {
        return new self("The given alias '" . $alias . "' is not unique " .
            "in FROM and JOIN clause table. The currently registered " .
            "aliases are: " . implode(", ", $registeredAliases) . ".");
    }
}
