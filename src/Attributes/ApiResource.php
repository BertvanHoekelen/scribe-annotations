<?php

declare(strict_types=1);

namespace Magwel\ScribeAnnotations\Attributes;

use Attribute;

#[Attribute]
class ApiResource
{
    /**
     * @param class-string<\Illuminate\Http\Resources\Json\JsonResource|\Illuminate\Http\Resources\Json\ResourceCollection> $resourceClass
     * @param class-string                                                                                                  $resourceModel
     */
    public function __construct(
        public string $resourceModel,
        public int $statusCode = 200,
        public ?string $resourceClass = null,
        public array $factoryStates = [],
        public array $relations = [],
        public ?int $perPage = null,
        public bool $simplePaginator = false,
    ) {
    }
}
