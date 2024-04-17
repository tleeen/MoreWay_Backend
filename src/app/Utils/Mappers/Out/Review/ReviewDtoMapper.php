<?php

namespace App\Utils\Mappers\Out\Review;

use App\Application\DTO\Out\Review\ReviewDto;
use App\Infrastructure\Database\Models\PlaceReview;
use App\Utils\Mappers\Out\Auth\UserDtoMapper;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class ReviewDtoMapper
{
    /**
     * @param PlaceReview|RouteReview $review
     * @return ReviewDto
     */
    public static function fromReviewModel(Model $review): ReviewDto
    {
        $createdAt = new Carbon($review->created_at);
        return new ReviewDto(
            id: $review->id,
            text: $review->text,
            rating: $review->rating,
            createdAt: $createdAt->timestamp,
            author: UserDtoMapper::fromUserModel($review->author),
        );
    }
}