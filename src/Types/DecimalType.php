<?php
namespace Sojf\DBAL\Types;

use Sojf\DBAL\Abstracts\Platform;
use Sojf\DBAL\Abstracts\Type;

/**
 * Type that maps an SQL DECIMAL to a PHP string.
 */
class DecimalType extends Type
{
    public function getName()
    {
        return Type::DECIMAL;
    }

    public function getSQLDeclaration(array $fieldDeclaration, Platform $platform)
    {
        return $platform->getDecimalTypeDeclarationSQL($fieldDeclaration);
    }

    public function convertToPHPValue($value, Platform $platform)
    {
        return (null === $value) ? null : $value;
    }
}
