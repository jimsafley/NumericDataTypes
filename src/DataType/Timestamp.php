<?php
namespace Timestamp\DataType;

use DateTime;
use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Representation\ValueRepresentation;
use Omeka\DataType\AbstractDataType;
use Omeka\Entity\Value;
use Zend\Form\Element;
use Zend\View\Renderer\PhpRenderer;

class Timestamp extends AbstractDataType
{
    public function getName()
    {
        return 'timestamp';
    }

    public function getLabel()
    {
        return 'Timestamp';
    }

    public function form(PhpRenderer $view)
    {
        $valueInput = new Element\Hidden('valuesuggest-value');
        $valueInput->setAttributes([
            'data-value-key' => '@value',
        ]);

        $yearInput = new Element\Text('timestamp-year');

        $monthSelect = new Element\Select('timestamp-month');
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

        $daySelect = new Element\Select('timestamp-day');
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
        $timestamp = $valueObject['@value'];
        // @see https://stackoverflow.com/a/2524761
        return ((string) (int) $timestamp === $timestamp) 
            && ($timestamp <= PHP_INT_MAX)
            && ($timestamp >= ~PHP_INT_MAX);
    }

    public function hydrate(array $valueObject, Value $value, AbstractEntityAdapter $adapter)
    {
        $value->setValue($valueObject['@value']);
        $value->setLang(null); // set default
        $value->setUri(null); // set default
        $value->setValueResource(null); // set default
    }

    public function render(PhpRenderer $view, ValueRepresentation $value)
    {
        $dateTime = new DateTime;
        $dateTime->setTimestamp((int) $value->value());
        return $dateTime->format('Y-m-d');
    }

    public function getJsonLd(ValueRepresentation $value)
    {
        return ['@value' => $value->value()];
    }
}
