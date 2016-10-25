<?php
namespace Sojf\DBAL\Exceptions;

/**
 * Doctrine\DBAL\ConnectionException
 *
 * @license http://www.opensource.org/licenses/mit-license.php MIT
 * @link    www.doctrine-project.org
 * @since   2.4
 * @author  Lars Strojny <lars@strojny.net>
 */
class SQLParserUtils extends DBAL
{
    /**
     * @param string $paramName
     *
     * @return SQLParserUtils
     */
    public static function missingParam($paramName)
    {
        return new self(sprintf('Value for :%1$s not found in params array. Params array key should be "%1$s"', $paramName));
    }

    /**
     * @param string $typeName
     *
     * @return SQLParserUtils
     */
    public static function missingType($typeName)
    {
        return new self(sprintf('Value for :%1$s not found in types array. Types array key should be "%1$s"', $typeName));
    }
}
