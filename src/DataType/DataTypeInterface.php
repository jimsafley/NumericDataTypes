<?php
namespace NumericDataTypes\DataType;

interface DataTypeInterface
{
    /**
     * Get the fully qualified name of the corresponding entity.
     *
     * @return string
     */
    public function getEntityClass();

    /**
     * Get the number to be stored from the passed value.
     *
     * @param string $value
     * @return int
     */
    public function getNumberFromValue($value);
}
