<?php

namespace App\Infrastructure\Database\Repositories\Place;

use App\Application\Contracts\Out\Repositories\IPlaceRepository;
use App\Application\DTO\In\Place\GetPlaceDto;
use App\Application\DTO\In\Place\GetPlacesDto;
use App\Application\Exceptions\Place\PlaceNotFound;
use App\Infrastructure\Database\Models\Filters\Place\PlaceFilterFactory;
use App\Infrastructure\Database\Models\Place;
use App\Infrastructure\Database\Repositories\BaseRepository\BaseRepository;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Query\Builder;

class PlaceRepository extends BaseRepository implements IPlaceRepository
{
    public function __construct(
        private readonly PlaceFilterFactory $placeFilterFactory,
        Place $place
    ) {
        parent::__construct($place);
    }

    /**
     * @param GetPlaceDto $getPlaceDto
     * @return Place
     * @throws PlaceNotFound
     */
    public function getPlaceById(GetPlaceDto $getPlaceDto): Place
    {
        /** @var Builder $place */
        $place = $this->model->query()->where('id', $getPlaceDto->id);

        if (!$place) {
            throw new PlaceNotFound();
        }

        /** @var Place */
        return $place->select('places.*')
            ->selectRaw(
                "ROUND(ST_Distance_Sphere(Point(places.lon, places.lat), Point(?, ?)) / 1000, 1) as distance",
                [$getPlaceDto->lon, $getPlaceDto->lat]
            )
            ->first();
    }

    /**
     * @param GetPlacesDto $getPlacesDto
     * @return CursorPaginator
     */
    public function getPlaces(GetPlacesDto $getPlacesDto): CursorPaginator
    {
        $paramsRequest = ($getPlacesDto->cursor !== null && $getPlacesDto->filter['sort'] !== null && $getPlacesDto->filter['sort']['sort'] === 'distance')
            ? [$getPlacesDto->lon, $getPlacesDto->lat, $getPlacesDto->lon, $getPlacesDto->lat]
            : [$getPlacesDto->lon, $getPlacesDto->lat];

        return $this->model->query()
            ->select('places.*')
            ->selectRaw(
                "ROUND(ST_Distance_Sphere(Point(places.lon, places.lat), Point(?, ?)) / 1000, 1) as distance",
                $paramsRequest
            )
            ->filter($this->placeFilterFactory->create($getPlacesDto->filter))
            ->cursorPaginate(perPage: $getPlacesDto->limit, cursor: $getPlacesDto->cursor);
    }
}