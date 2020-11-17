<?php

namespace App\Service\ETP\SberbankAST\SmallPurchasesSync\Interfaces;

/**
 * Interface IRequestBuilder
 * @package App\Service\ETP\SberbankAST\SmallPurchasesSync\Interfaces
 *
 * @author Poltarokov SP
 * 09.08.2020
 */
interface IRequestBuilder
{
    /**
     * Обрабатывает (устанавливает) параметры запроса
     * @param $params
     * @return array
     */
    public function produceFormData(array $params): array;

    /**
     * Устанавливает подтип источника данных, если таковой есть
     * у интегрируемой системы
     * @param $source
     */
    public function setImportSubSource(string $source): void;

    /**
     * Совершает дополнительные операции по подготовке запроса
     */
    public function buildUrl(): void;
}
