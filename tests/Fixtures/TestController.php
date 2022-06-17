<?php

declare(strict_types=1);

namespace Magwel\ScribeAnnotations\Tests\Fixtures;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Routing\Controller;
use Knuckles\Scribe\Tools\Utils;
use Magwel\ScribeAnnotations\Attributes\ApiResource;

/**
 * @group Group A
 */
class TestController extends Controller
{
    public function withEloquentApiResourceAnnotation(): TestUserApiResource
    {
        return new TestUserApiResource(Utils::getModelFactory(TestUser::class)->make(['id' => 0]));
    }

    #[ApiResource(TestUser::class)]
    public function withEloquentApiResourceAnnotationAndModel(): TestUserApiResource
    {
        return new TestUserApiResource(Utils::getModelFactory(TestUser::class)->make(['id' => 0]));
    }

    #[ApiResource(TestUser::class, 201, factoryStates: ['randomState'])]
    public function withEloquentApiResourceAnnotationWithStates(): TestUserApiResource
    {
        return new TestUserApiResource(Utils::getModelFactory(TestUser::class)->make(['id' => 0]));
    }

    #[ApiResource(TestUser::class, relations: ['children'])]
    public function withEloquentApiResourceAnnotationWithChildren(): TestUserApiResource
    {
        return new TestUserApiResource(Utils::getModelFactory(TestUser::class)->make(['id' => 0]));
    }

    #[ApiResource(TestUser::class, resourceClass: TestUserApiResource::class)]
    public function withEloquentApiResourceCollectionAnnotation(): AnonymousResourceCollection
    {
        return TestUserApiResource::collection(
            collect([Utils::getModelFactory(TestUser::class)->make(['id' => 0])])
        );
    }

    #[ApiResource(TestUser::class)]
    public function withEloquentApiResourceCollectionClassAnnotation(): TestUserApiResourceCollection
    {
        return new TestUserApiResourceCollection(
            collect([Utils::getModelFactory(TestUser::class)->make(['id' => 0])])
        );
    }

    #[ApiResource(TestUser::class, perPage: 1, simplePaginator: true)]
    public function withEloquentApiResourceCollectionClassWithSimplePaginationAnnotation(): TestUserApiResourceCollection
    {
        return new TestUserApiResourceCollection(
            new Paginator([
                Utils::getModelFactory(TestUser::class)->make(['id' => 0]),
                Utils::getModelFactory(TestUser::class)->make(['id' => 1]),
            ], 1)
        );
    }

    #[ApiResource(TestUser::class, perPage: 1)]
    public function withEloquentApiResourceCollectionClassWithPaginationAnnotation(): TestUserApiResourceCollection
    {
        $models = [
            Utils::getModelFactory(TestUser::class)->make(['id' => 0]),
            Utils::getModelFactory(TestUser::class)->make(['id' => 1]),
        ];

        $paginator = new LengthAwarePaginator(
            collect($models)->slice(0, 1),
            count($models),
            1
        );

        return new TestUserApiResourceCollection($paginator);
    }
}
