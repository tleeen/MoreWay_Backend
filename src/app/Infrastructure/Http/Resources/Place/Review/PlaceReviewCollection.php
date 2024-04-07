<?php

namespace App\Infrastructure\Http\Resources\Place\Review;

use App\Application\DTO\Collection\CursorDto;
use App\Infrastructure\Http\Resources\Auth\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin CursorDto
 */
class PlaceReviewCollection extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->data->map(function ($resource) {
                return [
                    'id' => $resource->id,
                    'text' => $resource->text,
                    'rating' => $resource->rating,
                    'createdAt' => $resource->created_at,
                    'author' => UserResource::make($resource->author),
                ];
            }),
            'meta' => [
                'next_cursor' => $this->next_cursor
            ]
        ];
    }
}
