<?php
namespace Sojf\DBAL\Exceptions;


/**
 * Conversion Exception is thrown when the database to PHP conversion fails.
 *
 * @link   www.doctrine-project.org
 * @since  2.0
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 * @author Guilherme Blanco <guilhermeblanco@hotmail.com>
 * @author Jonathan Wage <jonwage@gmail.com>
 * @author Roman Borschel <roman@code-factory.org>
 */

class Conversion extends DBAL
{
    /**
     * Thrown when a Database to Doctrine Type Conversion fails.
     *
     * @param string $value
     * @param string $toType
     *
     * @return Conversion
     */
    static public function conversionFailed($value, $toType)
    {
        $value = (strlen($value) > 32) ? substr($value, 0, 20) . "..." : $value;

        return new self('Could not convert database value "' . $value . '" to Doctrine Type ' . $toType);
    }

    /**
     * Thrown when a Database to Doctrine Type Conversion fails and we can make a statement
     * about the expected format.
     *
     * @param string $value
     * @param string $toType
     * @param string $expectedFormat
     *
     * @return Conversion
     */
    static public function conversionFailedFormat($value, $toType, $expectedFormat)
    {
        $value = (strlen($value) > 32) ? substr($value, 0, 20) . "..." : $value;

        return new self(
            'Could not convert database value "' . $value . '" to Doctrine Type ' .
            $toType . '. Expected format: ' . $expectedFormat
        );
    }
}
