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

        $yearInput = new Element\Text('numeric-timestamp-year');
        $yearInput->setAttribute('placeholder', 'Enter year'); // @translate

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

        $daySelect = new Element\Select('numeric-timestamp-day');
        $daySelect->setEmptyOption('Select day'); // @translate
        $daySelect->setValueOptions(array_combine(range(1, 31), range(1, 31)));

        return sprintf(
            '%s%s%s%s',
            $view->formText($yearInput),
            $view->formSelect($monthSelect),
            $view->formSelect($daySelect),
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
