<?php
namespace Sojf\DBAL\Types;


use Sojf\DBAL\Abstracts\Platform;
use Sojf\DBAL\Abstracts\Type;

/**
 * Represents a GUID/UUID datatype (both are actually synonyms) in the database.
 *
 * @author Benjamin Eberlei <kontakt@beberlei.de>
 * @since  2.3
 */
class GuidType extends StringType
{
    /**
     * {@inheritdoc}
     */
    public function getSQLDeclaration(array $fieldDeclaration, Platform $platform)
    {
        return $platform->getGuidTypeDeclarationSQL($fieldDeclaration);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return Type::GUID;
    }

    /**
     * {@inheritdoc}
     */
    public function requiresSQLCommentHint(Platform $platform)
    {
        return !$platform->hasNativeGuidType();
    }
}
