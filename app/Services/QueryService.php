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
            $cep = $this->clear($this->cepArray[$count - $i]);
            if ($this->verify($cep) == true) {
                $this->setToList($this->getData($cep));
            }
        }
        return $this->jsonResponse($this->cepList);
    }

    /**
     * @param  $data
     * @return array
     */
    public function getData($data): array
    {
        $cep = Http::get("https://viacep.com.br/ws/$data/json/")->json();
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
    public function clear($string): string
    {
        return preg_replace("/[^0-9]/", "", $string);
    }

    /**
     * @param $string
     * @return bool
     */
    public function verify($string): bool
    {
        if ($this->isEmpty($string)) {
            return false;
        }
        $cepArray = $this->cepList;
        if (!Arr::accessible($this->cepList)) {
            $this->cepList = [];
        } else {
            foreach ($cepArray as $array) {
                $cep = $this->clear($array['cep']);
                if ($cep == $string) {
                    return false;
                }
            }
        }
        return $this->isValid($string);
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
    public function isValid($string): bool
    {
        if (strlen($string) < 8 || strlen($string) > 8) {
            $this->setToList($this->messageService->badRequest($string));
            return false;
        }
        return true;
    }

    /**
     * @param $string
     */
    public function setToList($string)
    {
        array_push($this->cepList, $string);
    }

    /**
     * @param $data
     * @return JsonResponse
     */
    public function jsonResponse($data): JsonResponse
    {
        return response()->json($data, Response::HTTP_OK, ['Content-Type' => 'application/json', 'Charset' => 'utf-8'], JSON_UNESCAPED_UNICODE);
    }
}
