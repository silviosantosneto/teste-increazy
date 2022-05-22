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
    private $cepList = [];

    /**
     * @param $string
     * @return JsonResponse
     */

    public function get($string): JsonResponse
    {
        $this->convertStringToArray($string, ',');
        $count = count($this->cepArray);
        for ($i = 0; $i < $count; $i++) {
            $cep = $this->clearString($this->cepArray[$count - $i - 1]);
            if (strlen($cep) < 8 || strlen($cep) > 8) {
                $cepArray = [
                    'cep' => $cep,
                    'erro' => Response::HTTP_NOT_FOUND
                ];;
            } else {
                $cepArray = $this->getData($cep);
            }
            $this->sendToList($cepArray);
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
            $cep = [
                'cep' => $cep,
                'erro' => Response::HTTP_BAD_REQUEST
            ];;
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
     */
    public function sendToList($string)
    {
        array_push($this->cepList, $string);
    }
}
