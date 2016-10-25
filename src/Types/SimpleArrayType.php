<?php
namespace Sojf\DBAL\Types;


use Sojf\DBAL\Abstracts\Platform;
use Sojf\DBAL\Abstracts\Type;

/**
 * Array Type which can be used for simple values.
 *
 * Only use this type if you are sure that your values cannot contain a ",".
 *
 * @since  2.3
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class SimpleArrayType extends Type
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
    public function convertToDatabaseValue($value, Platform $platform)
    {
        if (!$value) {
            return null;
        }

        return implode(',', $value);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, Platform $platform)
    {
        if ($value === null) {
            return array();
        }

        $value = (is_resource($value)) ? stream_get_contents($value) : $value;

        return explode(',', $value);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return Type::SIMPLE_ARRAY;
    }

    /**
     * {@inheritdoc}
     */
    public function requiresSQLCommentHint(Platform $platform)
    {
        return true;
    }
}
