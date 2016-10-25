<?php
namespace Sojf\DBAL\Types;

use Sojf\DBAL\Exceptions\Conversion as ConversionException;
use Sojf\DBAL\Abstracts\Platform;
use Sojf\DBAL\Abstracts\Type;

/**
 * Type that maps an SQL DATETIME/TIMESTAMP to a PHP DateTime object.
 *
 * @since 2.0
 */
class DateTimeType extends Type
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return Type::DATETIME;
    }

    /**
     * {@inheritdoc}
     */
    public function getSQLDeclaration(array $fieldDeclaration, Platform $platform)
    {
        return $platform->getDateTimeTypeDeclarationSQL($fieldDeclaration);
    }

    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValue($value, Platform $platform)
    {
        return ($value !== null) ? $value->format($platform->getDateTimeFormatString()) : null;
    }

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value, Platform $platform)
    {
        if ($value === null || $value instanceof \DateTime) {
            return $value;
        }

        $val = \DateTime::createFromFormat($platform->getDateTimeFormatString(), $value);

        if ( ! $val) {

            $val = date_create($value);
        }

        if ( ! $val) {

            throw ConversionException::conversionFailedFormat($value, $this->getName(), $platform->getDateTimeFormatString());
        }

        return $val;
    }
}
