<?php
namespace Sojf\DBAL\Types;


use Sojf\DBAL\Abstracts\Platform;
use Sojf\DBAL\Abstracts\Type;

/**
 * Type that maps an SQL Set to a PHP string.
 *
 * @since 2.0
 */
class SetType extends Type
{
    /**
     * {@inheritdoc}
     */
    public function getSQLDeclaration(array $fieldDeclaration, Platform $platform)
    {
        return $platform->getSetTypeDeclarationSQL($fieldDeclaration);
    }
    
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return Type::SET;
    }
}
