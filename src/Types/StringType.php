<?php
namespace Sojf\DBAL\Types;


use Sojf\DBAL\Abstracts\Platform;
use Sojf\DBAL\Abstracts\Type;

/**
 * Type that maps an SQL VARCHAR to a PHP string.
 */
class StringType extends Type
{

    public function getSQLDeclaration(array $fieldDeclaration, Platform $platform)
    {
        return $platform->getVarcharTypeDeclarationSQL($fieldDeclaration);
    }

    public function getDefaultLength(Platform $platform)
    {
        return $platform->getVarcharDefaultLength();
    }

    public function getName()
    {
        return Type::STRING;
    }
}
