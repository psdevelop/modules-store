<?php

namespace App\Service\ETP\SberbankAST\SmallPurchasesSync\Request;

/**
 * Class Filter
 * @package App\Service\ETP\SberbankAST\SmallPurchasesSync\Request
 *
 * @author Poltarokov SP
 * 09.08.2020
 */
class Filter
{
    /** @var array $formData */
    private $formData = [];

    public function getFormData(): array
    {
        return $this->formData;
    }

    public function setFormData(array $data): void
    {
        $this->formData = $data;
    }

    public function addFormData(array $data): void
    {
        $this->formData = array_merge($this->formData, $data);
    }
}
