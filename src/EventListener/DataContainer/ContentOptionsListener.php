<?php

namespace HeimrichHannot\ResourceBookingBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\FormModel;
use Doctrine\DBAL\Connection;
use HeimrichHannot\ResourceBookingBundle\FormGenerator\ResourceBookingFormType;

readonly class ContentOptionsListener
{
    public function __construct(
        private Connection $connection,
    ) {}

    #[AsCallback('tl_content', 'fields.rb_form.options')]
    public function getFormOptions(): array
    {
        $result = $this->connection->createQueryBuilder()
            ->select('id', 'title')
            ->from(FormModel::getTable())
            ->where('formType = :formType')
            ->setParameter('formType', ResourceBookingFormType::TYPE)
            ->executeQuery();

        $options = [];

        foreach ($result->fetchAllAssociative() as $row) {
            $options[$row['id']] = "{$row['title']} [{$row['id']}]";
        }

        $result->free();

        return $options;
    }
}