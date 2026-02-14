<?php

namespace HeimrichHannot\ResourceBookingBundle\Widget;

use Contao\BackendTemplate;
use Contao\Controller;
use Contao\Widget;
use HeimrichHannot\ResourceBookingBundle\Contao\Table;
use HeimrichHannot\ResourceBookingBundle\Model\BookingResourceModel;
use HeimrichHannot\ResourceBookingBundle\Model\ResourceModel;

class BookedResourcesWidget extends Widget
{
    public const TYPE = 'huh_rb_bookedResources';

    protected $strTemplate = 'be_widget';
    protected string $resTemplate = 'widget/booked_resources_widget';

    public function __construct($arrAttributes = null)
    {
        Controller::loadLanguageFile($arrAttributes['strTable']);

        Controller::loadDataContainer(Table::RESOURCE->value);
        Controller::loadLanguageFile(Table::RESOURCE->value);

        parent::__construct($arrAttributes);
    }

    public function generate(): string
    {
        if ($this->strTable !== Table::BOOKING->value) {
            return '';
        }

        $template = new BackendTemplate($this->resTemplate);

        $template->table = $this->strTable;
        $bookingId = $this->objDca->id;
        $template->id = $bookingId;

        if (!$bookingResources = BookingResourceModel::findMultipleByPids([$bookingId])) {
            return 'Keine Ressourcen gebucht';
        }

        if (!$resourceIds = $bookingResources->fetchEach('resourceId')) {
            return 'Keine Ressourcen gebucht';
        }

        if (!$resources = ResourceModel::findMultipleByIds($resourceIds)) {
            return 'Keine Ressourcen gefunden';
        }

        $resources = \array_combine(
            $resources->fetchEach('id'),
            $resources->fetchAll(),
        );

        $list = [];

        foreach ($bookingResources as $bookingResource) {
            $list[$bookingResource->id] = [
                'booking_resource_id' => $bookingResource->id,
                'quantity' => $bookingResource->quantity,
                'resource' => $resources[$bookingResource->resourceId]
            ];
        }

        $template->list = $list;

        return $template->parse();
    }
}