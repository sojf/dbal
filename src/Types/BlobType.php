<?php
namespace Sojf\DBAL\Types;

use Sojf\DBAL\Exceptions\Conversion as ConversionException;
use Sojf\DBAL\Abstracts\Platform;
use Sojf\DBAL\Abstracts\Type;

/**
 * Type that maps an SQL BLOB to a PHP resource stream.
 *
 * @since 2.2
 */
class BlobType extends Type
{
    public function getSQLDeclaration(array $fieldDeclaration, Platform $platform)
    {
        return $platform->getBlobTypeDeclarationSQL($fieldDeclaration);
    }

    public function convertToPHPValue($value, Platform $platform)
    {
        if (null === $value) {
            return null;
        }

        if (is_string($value)) {
            $fp = fopen('php://temp', 'rb+');
            fwrite($fp, $value);
            fseek($fp, 0);
            $value = $fp;
        }

        if ( ! is_resource($value)) {
            throw ConversionException::conversionFailed($value, self::BLOB);
        }

        return $value;
    }

    public function getName()
    {
        return Type::BLOB;
    }

    public function getBindingType()
    {
        return \PDO::PARAM_LOB;
    }
}
