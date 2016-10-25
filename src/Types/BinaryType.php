<?php
namespace Sojf\DBAL\Types;

use Sojf\DBAL\Exceptions\Conversion as ConversionException;
use Sojf\DBAL\Abstracts\Platform;
use Sojf\DBAL\Abstracts\Type;

/**
 * Type that maps ab SQL BINARY/VARBINARY to a PHP resource stream.
 *
 * @author Steve Müller <st.mueller@dzh-online.de>
 * @since  2.5
 */
class BinaryType extends Type
{
    public function getSQLDeclaration(array $fieldDeclaration, Platform $platform)
    {
        return $platform->getBinaryTypeDeclarationSQL($fieldDeclaration);
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
            throw ConversionException::conversionFailed($value, self::BINARY);
        }

        return $value;
    }

    public function getName()
    {
        return Type::BINARY;
    }

    public function getBindingType()
    {
        return \PDO::PARAM_LOB;
    }
}
