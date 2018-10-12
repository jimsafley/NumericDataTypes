<?php
namespace NumericDataTypes\DataType;

use Omeka\Api\Representation\ValueRepresentation;
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

    public function isValid(array $valueObject)
    {
        return $this->stringIsValidInteger($valueObject['@value']);
    }

    public function render(PhpRenderer $view, ValueRepresentation $value)
    {
        return $value->value();
    }
}
