<?php
namespace Sojf\DBAL\Types;


use Sojf\DBAL\Exceptions\Conversion as ConversionException;

use Sojf\DBAL\Abstracts\Platform;
use Sojf\DBAL\Abstracts\Type;

/**
 * Type that maps a PHP array to a clob SQL type.
 */
class ArrayType extends Type
{
    public function getSQLDeclaration(array $fieldDeclaration, Platform $platform)
    {
        return $platform->getClobTypeDeclarationSQL($fieldDeclaration);
    }

    public function convertToDatabaseValue($value, Platform $platform)
    {
        // @todo 3.0 - $value === null check to save real NULL in database
        return serialize($value);
    }

    public function convertToPHPValue($value, Platform $platform)
    {
        if ($value === null) {
            return null;
        }

        $value = (is_resource($value)) ? stream_get_contents($value) : $value;

        $val = unserialize($value);

        if ($val === false && $value != 'b:0;') {
            
            throw ConversionException::conversionFailed($value, $this->getName());
        }

        return $val;
    }

    public function getName()
    {
        return Type::TARRAY;
    }

    public function requiresSQLCommentHint(Platform $platform)
    {
        return true;
    }
}
