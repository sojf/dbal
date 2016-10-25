<?php
namespace Sojf\DBAL\Types;


use Sojf\DBAL\Abstracts\Platform;
use Sojf\DBAL\Abstracts\Type;

/**
 * Type that maps a database SMALLINT to a PHP integer.
 *
 * @author robo
 */
class SmallIntType extends Type
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return Type::SMALLINT;
    }

    /**
     * {@inheritdoc}
     */
    public function getSQLDeclaration(array $fieldDeclaration, Platform $platform)
    {
        return $platform->getSmallIntTypeDeclarationSQL($fieldDeclaration);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, Platform $platform)
    {
        return (null === $value) ? null : (int) $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getBindingType()
    {
        return \PDO::PARAM_INT;
    }
}
