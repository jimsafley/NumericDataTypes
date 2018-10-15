<?php
namespace NumericDataTypes\DataType;

use DateTime;
use Omeka\Api\Representation\ValueRepresentation;
use Zend\Form\Element;
use Zend\View\Renderer\PhpRenderer;

class Timestamp extends AbstractDataType
{
    public function getName()
    {
        return 'numeric:timestamp';
    }

    public function getLabel()
    {
        return 'Timestamp';
    }

    public function form(PhpRenderer $view)
    {
        $valueInput = new Element\Hidden('numeric-timestamp-value');
        $valueInput->setAttributes([
            'data-value-key' => '@value',
        ]);

        $yearInput = new Element\Number('numeric-timestamp-year');
        $yearInput->setAttributes([
            'step' => 1,
            'placeholder' => 'Enter year', // @translate
        ]);

        $monthSelect = new Element\Select('numeric-timestamp-month');
        $monthSelect->setEmptyOption('Select month'); // @translate
        $monthSelect->setValueOptions([
            'January', // @translate
            'February', // @translate
            'March', // @translate
            'April', // @translate
            'May', // @translate
            'June', // @translate
            'July', // @translate
            'August', // @translate
            'September', // @translate
            'October', // @translate
            'November', // @translate
            'December', // @translate
        ]);

        $dayInput = new Element\Number('numeric-timestamp-day');
        $dayInput->setAttributes([
            'step' => 1,
            'min' => 1,
            'max' => 31,
            'placeholder' => 'Enter day', // @translate
        ]);

        return sprintf(
            '%s%s%s%s',
            $view->formNumber($yearInput),
            $view->formSelect($monthSelect),
            $view->formNumber($dayInput),
            $view->formHidden($valueInput)
        );
    }

    public function isValid(array $valueObject)
    {
        return $this->stringIsValidInteger($valueObject['@value']);
    }

    public function render(PhpRenderer $view, ValueRepresentation $value)
    {
        $dateTime = new DateTime;
        $dateTime->setTimestamp((int) $value->value());
        return $dateTime->format('Y-m-d');
    }
}
