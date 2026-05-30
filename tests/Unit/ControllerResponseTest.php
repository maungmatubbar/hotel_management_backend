<?php

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

uses(TestCase::class);

test('json responses include response metadata', function () {
    $response = testController()->exposeJsonResponse([
        'name' => 'Demo',
    ]);
    $payload = responsePayload($response);

    expect($response->isSuccessful())->toBeTrue()
        ->and($payload['name'])->toBe('Demo')
        ->and($payload)->not->toHaveKey('refresh_token')
        ->and($payload['response_code'])->toMatch('/^[0-9a-f]{13}$/');
});

test('success and error responses include response code', function () {
    $successResponse = testController()->exposeSuccessResponse(
        data: ['id' => 1],
    );
    $successPayload = responsePayload($successResponse);

    expect($successResponse->isSuccessful())->toBeTrue()
        ->and($successPayload['success'])->toBeTrue()
        ->and($successPayload['data']['id'])->toBe(1)
        ->and($successPayload)->not->toHaveKey('refresh_token')
        ->and($successPayload['response_code'])->toMatch('/^[0-9a-f]{13}$/');

    $errorResponse = testController()->exposeErrorResponse(
        message: 'Invalid request',
        errors: ['name' => ['The name field is required.']],
    );
    $errorPayload = responsePayload($errorResponse);

    expect($errorResponse->getStatusCode())->toBe(Response::HTTP_BAD_REQUEST)
        ->and($errorPayload['success'])->toBeFalse()
        ->and($errorPayload['message'])->toBe('Invalid request')
        ->and($errorPayload['errors']['name'][0])->toBe('The name field is required.')
        ->and($errorPayload)->not->toHaveKey('refresh_token')
        ->and($errorPayload['response_code'])->toMatch('/^[0-9a-f]{13}$/');
});

/**
 * @return array<string, mixed>
 */
function responsePayload(JsonResponse $response): array
{
    return json_decode($response->getContent(), true, flags: JSON_THROW_ON_ERROR);
}

function testController(): ControllerResponseTestController
{
    return new ControllerResponseTestController;
}

class ControllerResponseTestController extends Controller
{
    public function exposeJsonResponse(mixed $data): JsonResponse
    {
        return $this->jsonResponse($data);
    }

    public function exposeSuccessResponse(
        mixed $data,
    ): JsonResponse {
        return $this->successResponse(data: $data);
    }

    /**
     * @param  array<string, mixed>  $errors
     */
    public function exposeErrorResponse(
        string $message,
        array $errors = [],
    ): JsonResponse {
        return $this->errorResponse(
            message: $message,
            errors: $errors,
        );
    }
}
