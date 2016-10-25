<?php
namespace Sojf\DBAL\Types;


use Sojf\DBAL\Abstracts\Platform;
use Sojf\DBAL\Abstracts\Type;

use Sojf\DBAL\Exceptions\Conversion as ConversionException;


/**
 * Type that maps an SQL TIME to a PHP DateTime object.
 */
class TimeType extends Type
{
    public function getName()
    {
        return Type::TIME;
    }

    public function getSQLDeclaration(array $fieldDeclaration, Platform $platform)
    {
        return $platform->getTimeTypeDeclarationSQL($fieldDeclaration);
    }

    public function convertToDatabaseValue($value, Platform $platform)
    {
        return ($value !== null)
            ? $value->format($platform->getTimeFormatString()) : null;
    }

    public function convertToPHPValue($value, Platform $platform)
    {
        if ($value === null || $value instanceof \DateTime) {
            return $value;
        }

        $val = \DateTime::createFromFormat('!' . $platform->getTimeFormatString(), $value);

        if ( ! $val) {
            throw ConversionException::conversionFailedFormat($value, $this->getName(), $platform->getTimeFormatString());
        }

        return $val;
    }
}
