<?php

namespace App\Services;

use App\Models\Address;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Symfony\Component\HttpFoundation\Response;

class QueryService
{
    private $cepArray;
    private $cepList;
    private $cepObject;
    private $messageService;

    public function __construct(MessageService $messageService, Address $cepObject)
    {
        $this->messageService = $messageService;
        $this->cepObject = $cepObject;
    }

    /**
     * @param $string
     * @return JsonResponse
     */

    public function get($string): JsonResponse
    {
        $this->convertStringToArray($string);
        $count = count($this->cepArray);
        $i = 0;
        while ($i < $count) {
            $i++;
            $cep = $this->clear($this->cepArray[$count - $i]);
            if ($this->verify($cep) == true) {
                $this->getData($this->format($cep));
            }
        }
        return $this->jsonResponse($this->cepList);
    }

    /**
     * @param  $data
     */
    private function getData($data)
    {
        if (Address::where('cep', '=', $data)->exists()) {
            $cep = Address::where('cep', '=', $data)->first();
            $cep = $cep->toArray();
        } else {
            $cep = Http::get("https://viacep.com.br/ws/$data/json/")->json();
            if (Arr::exists($cep, 'erro')) {
                $cep = $this->messageService->notFound($cep);
            }
            $this->cepObject->fill($cep)->save();
        }
        $this->fillList($cep);
    }

    /**
     * @param $string
     */
    private function convertStringToArray($string)
    {
        $this->cepArray = Str::of($string)->explode(',');
    }

    /**
     * @param $string
     * @return string
     */
    private function clear($string): string
    {
        return preg_replace("/[^0-9]/", "", $string);

    }

    /**
     * @param $data
     * @return string
     */
    private function format($data): string
    {
        return substr_replace($data,'-', 5, 0);
    }

    /**
     * @param $string
     * @return bool
     */
    private function verify($string): bool
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
    private function isEmpty($string): bool
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
    private function isValid($string): bool
    {
        if (strlen($string) < 8 || strlen($string) > 8) {
            $this->fillList($this->messageService->badRequest($string));
            return false;
        }
        return true;
    }

    /**
     * @param $string
     */
    private function fillList($string)
    {
        array_push($this->cepList, $string);
    }

    /**
     * @param $data
     * @return JsonResponse
     */
    private function jsonResponse($data): JsonResponse
    {
        return response()->json($data, Response::HTTP_OK, ['Content-Type' => 'application/json', 'Charset' => 'utf-8'], JSON_UNESCAPED_UNICODE);
    }
}
