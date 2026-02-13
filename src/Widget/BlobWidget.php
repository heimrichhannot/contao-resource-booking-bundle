<?php

namespace HeimrichHannot\ResourceBookingBundle\Widget;

use Contao\BackendTemplate;
use Contao\Controller;
use Contao\StringUtil;
use Contao\Widget;

class BlobWidget extends Widget
{
    public const TYPE = 'huh_rb_blobTable';

    protected $strTemplate = 'be_widget';
    protected $blobTemplate = 'widget/blob_widget';
    protected array $dcaField;

    public function __construct($arrAttributes = null)
    {
        $table = $arrAttributes['strTable'];
        $field = $arrAttributes['strField'];

        Controller::loadLanguageFile($table);

        $this->dcaField = $GLOBALS['TL_DCA'][$table]['fields'][$field];

        parent::__construct($arrAttributes);
    }

    public function generate(): string
    {
        $template = new BackendTemplate($this->blobTemplate);

        $template->table = $this->strTable;
        $template->id = $this->objDca->id;

        if (!$data = StringUtil::deserialize($this->varValue)) {
            $data = null;
        }

        $template->data = (array) $data;
        $template->is_array = static fn (mixed $value): bool => \is_array($value);

        return $template->parse();
    }
}