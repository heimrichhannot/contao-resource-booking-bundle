<?php

namespace HeimrichHannot\ResourceBookingBundle\FormGenerator;

use Contao\DataContainer;
use Contao\FormModel;
use HeimrichHannot\FormTypeBundle\FormType\AbstractFormType;

class ResourceBookingFormType extends AbstractFormType
{
    public const TYPE = 'huh_resource_booking';

    public function getType(): string
    {
        return static::TYPE;
    }

    public function onload(DataContainer $dataContainer, FormModel $formModel): void {}

    public function getDefaultFields(FormModel $formModel): array
    {
        return [
            'email' => [
                'type' => 'email',
                'label' => 'Email address',
                'required' => true,
            ],
        ];
    }
}