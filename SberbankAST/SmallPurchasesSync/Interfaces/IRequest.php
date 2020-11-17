<?php


namespace App\Service\ETP\SberbankAST\SmallPurchasesSync\Interfaces;

/**
 * Interface IRequest
 * @package App\Service\ETP\SberbankAST\SmallPurchasesSync\Interfaces
 *
 * @author Poltarokov SP
 * 10.08.2020
 */
interface IRequest
{
    /**
     * Возвращает метод HTTP используемый запросом
     * @return string
     */
    public function getMethod(): string;

    /**
     * Возвращает url запроса
     * @return string
     */
    public function getUrl(): string;

    /**
     * Возвращает массив с заголовками запроса
     * @return array
     */
    public function getHeaders(): array;

    /**
     * Возвращает параметры POST-запроса
     * @return array
     */
    public function getPostFields(): array;

    /**
     * Возвращает подтип запроса при использовании формы
     * @return int
     */
    public function getFormDataType(): int;

    /**
     * Возвращает подтип источника (в случае если их в
     * интегрируемой системе несколько)
     * @return string
     */
    public function getImportSubSource(): string;
}
