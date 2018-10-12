<?php
namespace NumericDataTypes\DataType;

use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Representation\ValueRepresentation;
use Omeka\DataType\AbstractDataType as BaseAbstractDataType;
use Omeka\Entity\Value;

abstract class AbstractDataType extends BaseAbstractDataType
{
    public function getOptgroupLabel()
    {
        return 'Numeric'; // @translate
    }

    public function hydrate(array $valueObject, Value $value, AbstractEntityAdapter $adapter)
    {
        $value->setValue($valueObject['@value']);
        $value->setLang(null); // set default
        $value->setUri(null); // set default
        $value->setValueResource(null); // set default
    }

    public function getJsonLd(ValueRepresentation $value)
    {
        return ['@value' => $value->value()];
    }

    public function stringIsValidInteger($integer)
    {
        // @see https://stackoverflow.com/a/2524761
        return ((string) (int) $integer === $integer) 
            && ($integer <= PHP_INT_MAX)
            && ($integer >= ~PHP_INT_MAX);
    }
}

