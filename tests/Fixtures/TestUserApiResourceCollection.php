<?php

declare(strict_types=1);

namespace Magwel\ScribeAnnotations\Tests\Fixtures;

use Illuminate\Http\Resources\Json\ResourceCollection;

class TestUserApiResourceCollection extends ResourceCollection
{
    /**
     * Transform the resource into an array.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return array
     */
    public function toArray($request)
    {
        $data = [
            'data' => $this->collection,
            'links' => [
                'self' => 'link-value',
            ],
        ];

        return $data;
    }
}
