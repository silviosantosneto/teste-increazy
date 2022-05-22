<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\QueryService;
use Illuminate\Http\JsonResponse;

class QueryController extends Controller
{
    private $queryService;

    /**
     * @param QueryService $queryService
     */
    public function __construct(QueryService $queryService)
    {
        $this->queryService = $queryService;
    }

    /**
     * @param $data
     * @return JsonResponse
     */
    public function getCepData($data): JsonResponse
    {
        return $this->queryService->get($data);
    }
}
