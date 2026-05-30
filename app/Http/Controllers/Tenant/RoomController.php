<?php

namespace App\Http\Controllers\Tenant;

use App\Application\Room\CreateRoomUseCase;
use App\Application\Room\DeleteRoomUseCase;
use App\Application\Room\GetRoomsUseCase;
use App\Application\Room\UpdateRoomUseCase;
use App\Domain\Room\DTO\RoomDataRequest;
use App\Domain\Room\DTO\RoomDataResponse;
use App\Domain\Room\DTO\RoomUpdateDataRequest;
use App\Http\Controllers\Controller;
use App\Models\Room;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoomController extends Controller
{
    public function index(GetRoomsUseCase $getRoomsUseCase): JsonResponse
    {
        return $this->successResponse(
            data: $getRoomsUseCase()
                ->map(fn (Room $room): RoomDataResponse => RoomDataResponse::fromRoom($room))
                ->values()
                ->all(),
        );
    }

    public function store(Request $request, CreateRoomUseCase $createRoomUseCase): JsonResponse
    {
        return $this->successResponse(
            data: RoomDataResponse::fromRoom($createRoomUseCase(RoomDataRequest::from($request))),
            status: Response::HTTP_CREATED,
        );
    }

    public function show(string $tenant, int $room): JsonResponse
    {
        return $this->successResponse(
            data: RoomDataResponse::fromRoom($this->findRoom($room)),
        );
    }

    public function update(Request $request, string $tenant, int $room, UpdateRoomUseCase $updateRoomUseCase): JsonResponse
    {
        return $this->successResponse(
            data: RoomDataResponse::fromRoom($updateRoomUseCase($this->findRoom($room), RoomUpdateDataRequest::from($request))),
        );
    }

    public function destroy(string $tenant, int $room, DeleteRoomUseCase $deleteRoomUseCase): JsonResponse
    {
        $deleteRoomUseCase($this->findRoom($room));

        return $this->successResponse(
            data: [],
        );
    }

    private function findRoom(int $room): Room
    {
        return Room::query()
            ->with([
                'files' => fn ($query) => $query->where('category', Room::IMAGE_CATEGORY),
            ])
            ->findOrFail($room);
    }
}
