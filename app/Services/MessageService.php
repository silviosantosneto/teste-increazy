<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\Response;

class MessageService
{
    /**
     * @param string $info
     * @return array
     */
    public function notFound(string $info): array
    {
        return [
            'cep' => $info,
            'erro' => Response::HTTP_NOT_FOUND
        ];
    }

    /**
     * @param string $info
     * @return array
     */
    public function badRequest(string $info): array
    {
        return [
            'cep' => $info,
            'erro' => Response::HTTP_BAD_REQUEST
        ];

    }
}
