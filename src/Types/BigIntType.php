<?php
namespace Sojf\DBAL\Types;


use Sojf\DBAL\Abstracts\Platform;
use Sojf\DBAL\Abstracts\Type;

/**
 * Type that maps a database BIGINT to a PHP string.
 *
 * @author robo
 * @since 2.0
 */
class BigIntType extends Type
{
    public function getName()
    {
        return self::BIGINT;
    }
    
    public function getSQLDeclaration(array $fieldDeclaration, Platform $platform)
    {
        return $platform->getBigIntTypeDeclarationSQL($fieldDeclaration);
    }
    
    public function getBindingType()
    {
        return \PDO::PARAM_STR;
    }
    
    public function convertToPHPValue($value, Platform $platform)
    {
        return (null === $value) ? null : (string) $value;
    }
}
