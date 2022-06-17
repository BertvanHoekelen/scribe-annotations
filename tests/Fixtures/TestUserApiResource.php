<?php

declare(strict_types=1);

namespace Magwel\ScribeAnnotations\Tests\Fixtures;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin \Magwel\ScribeAnnotations\Tests\Fixtures\TestUser
 */
class TestUserApiResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->first_name . ' ' . $this->last_name,
            'email' => $this->email,
            'children' => $this->whenLoaded('children', function () {
                return TestUserApiResource::collection($this->children);
            }),
            'random-state' => $this->when((bool) $this->randomState, fn () => $this->randomState),
        ];
    }
}
