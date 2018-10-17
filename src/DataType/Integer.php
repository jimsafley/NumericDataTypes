<?php
namespace NumericDataTypes\DataType;

use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Representation\ValueRepresentation;
use Omeka\Entity\Value;
use Zend\Form\Element;
use Zend\View\Renderer\PhpRenderer;

class Integer extends AbstractDataType
{
    public function getName()
    {
        return 'numeric:integer';
    }

    public function getLabel()
    {
        return 'Integer';
    }

    public function form(PhpRenderer $view)
    {
        $valueInput = new Element\Number('numeric-integer-value');
        $valueInput->setAttributes([
            'data-value-key' => '@value',
            'step' => 1,
        ]);
        return $view->formNumber($valueInput);
    }

    public function hydrate(array $valueObject, Value $value, AbstractEntityAdapter $adapter)
    {
        $value->setValue($valueObject['@value']);
        $value->setLang(null);
        $value->setUri(null);
        $value->setValueResource(null);
    }

    public function isValid(array $valueObject)
    {
        return ($valueObject['@value'] <= PHP_INT_MAX)
            && ($valueObject['@value'] >= ~PHP_INT_MAX);
    }

    public function render(PhpRenderer $view, ValueRepresentation $value)
    {
        return $value->value();
    }

    public function getEntityClass()
    {
        return '\NumericDataTypes\Entity\NumericDataTypesInteger';
    }

    /**
     * Get the integer from the value.
     *
     * @param string $value
     * @return int
     */
    public function getNumberFromValue($value)
    {
        return (int) $value;
    }
}
