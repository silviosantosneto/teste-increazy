<?php

namespace App\Services;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class QueryService
{
    private $cepArray;
    private $cepList;
    private $messageService;

    public function __construct(MessageService $messageService)
    {
        $this->messageService = $messageService;
    }

    /**
     * @param $string
     * @return JsonResponse
     */

    public function get($string): JsonResponse
    {
        $this->convertStringToArray($string, ',');
        $count = count($this->cepArray);
        $i = 0;
        while ($i < $count) {
            $i++;
            $cep = $this->clearString($this->cepArray[$count - $i]);
            if ($this->verifyString($cep) == true) {
                $this->sendToList($this->getData($cep));
            }
        }
        return response()->json($this->cepList, Response::HTTP_OK, ['Content-Type' => 'application/json;charset=UTF-8', 'Charset' => 'utf-8'], JSON_PRETTY_PRINT);
    }

    /**
     * @param  $data
     * @return array
     */
    public function getData($data): array
    {
        $cep = Http::get("https://viacep.com.br/ws/{$data}/json/")->json();
        if (Arr::exists($cep, 'erro')) {
            $cep = $this->messageService->notFound($cep);
        }
        return $cep;
    }

    /**
     * @param $string
     * @param $delimiter
     */
    public function convertStringToArray($string, $delimiter)
    {
        $this->cepArray = Str::of($string)->explode($delimiter);
    }

    /**
     * @param $string
     * @return string
     */
    public function clearString($string): string
    {
        return preg_replace("/[^0-9]/", "", $string);
    }

    /**
     * @param $string
     * @return bool
     */
    public function verifyString($string): bool
    {
        if ($this->isEmpty($string)){
            return false;
        }
        $cepArray = $this->cepList;
        if (!Arr::accessible($this->cepList)) {
            $this->cepList = [];
        } else {
            foreach ($cepArray as $array) {
                $cep = $this->clearString($array['cep']);
                if ($cep == $string) {
                    return false;
                }
            }
        }
        return $this->isValidCEP($string);
    }

    /**
     * @param $string
     * @return bool
     */
    public function isEmpty($string): bool
    {
        if ($string == '') {
            return true;
        }
        return false;
    }

    /**
     * @param $string
     * @return bool
     */
    public function isValidCEP($string): bool
    {
        if (strlen($string) < 8 || strlen($string) > 8) {
            $this->sendToList($this->messageService->badRequest($string));
            return false;
        }
        return true;
    }

    /**
     * @param $string
     */
    public function sendToList($string)
    {
        array_push($this->cepList, $string);
    }
}
