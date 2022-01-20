<?php

namespace srag\Plugins\Opencast\Model\Metadata\Config\Event;

use srag\Plugins\Opencast\Model\Metadata\Config\MDFieldConfigAR;
use srag\Plugins\Opencast\Model\Metadata\Config\MDFieldConfigRepository;
use srag\Plugins\Opencast\Model\Metadata\Config\MDPrefillOption;
use srag\Plugins\Opencast\Model\Metadata\Definition\MDCatalogueFactory;
use xoctException;

class MDFieldConfigEventRepository implements MDFieldConfigRepository
{
    /**
     * @var MDCatalogueFactory
     */
    private $MDCatalogueFactory;

    /**
     * @param MDCatalogueFactory $MDCatalogueFactory
     */
    public function __construct(MDCatalogueFactory $MDCatalogueFactory)
    {
        $this->MDCatalogueFactory = $MDCatalogueFactory;
    }

    /**
     * @return MDFieldConfigEventAR[]
     */
    public function getAll(): array
    {
        return MDFieldConfigEventAR::orderBy('sort')->get();
    }

    /**
     * Important: this returns all fields that are defined as read_only by the Opencast Metadata Catalogue - NOT ONLY by the
     * metadata field configuration in the plugin. This is an important distinction, since fields that are read_only in
     * the plugin but NOT read_only in Opencast might still be prefilled, e.g. with the course title or current username.
     *
     * @return array|MDFieldConfigAR[]
     * @throws xoctException
     */
    public function getAllForForm(): array
    {
        $MDCatalogue = $this->MDCatalogueFactory->event();
        return array_filter(MDFieldConfigEventAR::orderBy('sort')->get(),
            function (MDFieldConfigEventAR $ar) use ($MDCatalogue) {
                return !$MDCatalogue->getFieldById($ar->getFieldId())->isReadOnly();
            });
    }

    public function getArray(): array
    {
        return MDFieldConfigEventAR::orderBy('sort')->getArray();
    }

    public function findByFieldId(string $field_id): ?MDFieldConfigAR
    {
        /** @var MDFieldConfigEventAR $ar */
        $ar = MDFieldConfigEventAR::where(['field_id' => $field_id])->first();
        return $ar;
    }

    public function storeFromArray(array $data): MDFieldConfigAR
    {
        $ar = MDFieldConfigEventAR::where(['field_id' => $data['field_id']])->first();
        if (is_null($ar)) {
            $ar = new MDFieldConfigEventAR();
        }
        $ar->setFieldId($data['field_id']);
        $ar->setTitleDe($data['title_de']);
        $ar->setTitleEn($data['title_en']);
        $ar->setVisibleForPermissions($data['visible_for_permissions']);
        $ar->setPrefill(new MDPrefillOption($data['prefill']));
        $ar->setReadOnly($data['read_only']);
        $ar->setRequired($data['required']);
        $ar->setSort($this->getNextSort());
        $ar->store();
        return $ar;
    }

    /**
     * @return MDFieldConfigEventAR[]
     * @throws xoctException
     */
    function getAllFilterable(): array
    {
        $catalogue = $this->MDCatalogueFactory->event();
        return array_filter($this->getAll(), function (MDFieldConfigEventAR $fieldConfig) use ($catalogue) {
            return $catalogue->getFieldById($fieldConfig->getFieldId())
                ->getType()->isFilterable();
        });
    }

    private function getNextSort(): int
    {
        /** @var MDFieldConfigEventAR $highest */
        $highest = MDFieldConfigEventAR::orderBy('sort', 'desc')->first();
        return $highest ? ($highest->getSort() + 1) : 1;
    }
}