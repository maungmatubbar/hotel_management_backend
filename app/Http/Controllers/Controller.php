<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\JsonResponse;
use JsonSerializable;
use Symfony\Component\HttpFoundation\Response;

abstract class Controller
{
    /**
     * @param  array<string, string>  $headers
     */
    protected function jsonResponse(
        mixed $data = [],
        int $status = Response::HTTP_OK,
        array $headers = [],
        int $options = 0,
    ): JsonResponse {
        $data = $this->withResponseMetadata($data);

        return response()->json($data, $status, $headers, $options);
    }

    /**
     * @param  array<string, string>  $headers
     */
    protected function successResponse(
        mixed $data = [],
        string $message = 'Success',
        int $status = Response::HTTP_OK,
        array $headers = [],
        int $options = 0,
    ): JsonResponse {
        return $this->jsonResponse([
            'success' => true,
            'message' => $message,
            'data' => $data,
        ], $status, $headers, $options);
    }

    /**
     * @param  array<string, mixed>  $errors
     * @param  array<string, string>  $headers
     */
    protected function errorResponse(
        string $message = 'Error',
        array $errors = [],
        int $status = Response::HTTP_BAD_REQUEST,
        array $headers = [],
        int $options = 0,
    ): JsonResponse {
        return $this->jsonResponse([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], $status, $headers, $options);
    }

    /**
     * @return array<string, mixed>
     */
    private function withResponseMetadata(mixed $data): array
    {
        if ($data instanceof Arrayable) {
            $data = $data->toArray();
        }

        if ($data instanceof JsonSerializable) {
            $data = $data->jsonSerialize();
        }

        if (! is_array($data)) {
            $data = ['data' => $data];
        }

        return [
            ...$data,
            'response_code' => uniqid(),
        ];
    }
}
