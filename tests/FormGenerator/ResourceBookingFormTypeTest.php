<?php

namespace HeimrichHannot\ResourceBookingBundle\Tests\FormGenerator;

use Contao\FormModel;
use HeimrichHannot\ResourceBookingBundle\FormGenerator\ResourceBookingFormType;
use PHPUnit\Framework\TestCase;

class ResourceBookingFormTypeTest extends TestCase
{
    public function testDefaultEmailFieldUsesContaoFormFieldProperties(): void
    {
        $formModel = $this->createMock(FormModel::class);
        $fields = (new ResourceBookingFormType())->getDefaultFields($formModel);

        $this->assertSame('text', $fields['email']['type']);
        $this->assertSame('email', $fields['email']['name']);
        $this->assertSame('email', $fields['email']['rgxp']);
        $this->assertTrue($fields['email']['mandatory']);
    }
}
