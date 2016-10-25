<?php
namespace Sojf\DBAL\Types;


use Sojf\DBAL\Abstracts\Platform;
use Sojf\DBAL\Abstracts\Type;


/**
 * Type that maps an SQL CLOB to a PHP string.
 *
 * @since 2.0
 */
class TextType extends Type
{
    /**
     * {@inheritdoc}
     */
    public function getSQLDeclaration(array $fieldDeclaration, Platform $platform)
    {
        return $platform->getClobTypeDeclarationSQL($fieldDeclaration);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, Platform $platform)
    {
        return (is_resource($value)) ? stream_get_contents($value) : $value;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return Type::TEXT;
    }
}
