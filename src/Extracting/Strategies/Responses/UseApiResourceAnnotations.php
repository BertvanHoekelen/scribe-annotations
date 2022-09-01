<?php

declare(strict_types=1);

namespace Magwel\ScribeAnnotations\Extracting\Strategies\Responses;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Knuckles\Camel\Extraction\ExtractedEndpointData;
use Knuckles\Scribe\Extracting\DatabaseTransactionHelpers;
use Knuckles\Scribe\Extracting\Strategies\Strategy;
use Knuckles\Scribe\Tools\ConsoleOutputUtils as c;
use Knuckles\Scribe\Tools\ErrorHandlingUtils as e;
use Knuckles\Scribe\Tools\Utils;
use Magwel\ScribeAnnotations\Attributes\ApiResource;
use Mpociot\Reflection\DocBlock;
use ReflectionClass;
use ReflectionMethod;
use ReflectionUnionType;
use Throwable;

class UseApiResourceAnnotations extends Strategy
{
    use DatabaseTransactionHelpers;

    public function __invoke(ExtractedEndpointData $endpointData, array $routeRules): ?array
    {
        if (! $endpointData->route) {
            return null;
        }

        /** @var ReflectionMethod $method */
        $method = Utils::getReflectedRouteMethod(Utils::getRouteClassAndMethodNames($endpointData->route));

        $apiResourceAttribute = $method->getAttributes(ApiResource::class)[0] ?? null;
        $returnTypes = $method->getReturnType();

        /** @var class-string<JsonResource> $returnType */
        $returnType = ($returnTypes instanceof ReflectionUnionType) ? $returnTypes->getTypes()[0]->getName() : $returnTypes->getName(); /* @phpstan-ignore-line */

        $this->startDbTransaction();

        try {
            if ($apiResourceAttribute) {
                return $this->getApiResourceResponseFromAttribute($endpointData, $returnType, $apiResourceAttribute->newInstance());
            }

            return $this->fromDocBlocks($endpointData, $returnType);
        } catch (Exception $e) {
            c::warn('Exception thrown when fetching Eloquent API resource response for ' . $endpointData->name());
            e::dumpExceptionIfVerbose($e);

            return null;
        } finally {
            $this->endDbTransaction();
        }
    }

    /**
     * @param class-string<JsonResource> $returnType
     */
    public function fromDocBlocks(ExtractedEndpointData $endpointData, string $returnType): ?array
    {
        $class = new ReflectionClass($returnType);
        $docBlock = new DocBlock($class->getDocComment() ?: '');

        /** @var \Mpociot\Reflection\DocBlock\Tag|null $tag */
        $tag = collect($docBlock->getTags())->first(fn (DocBlock\Tag $tag) => $tag->getName() === 'mixin');

        if ($tag && $content = $tag->getContent()) {
            return $this->getApiResourceResponseFromReturnType($endpointData, $returnType, $content);
        }

        return null;
    }

    public function getApiResourceResponseFromReturnType(ExtractedEndpointData $endpointData, string $returnType, string $resourceModel): ?array
    {
        return $this->getApiResourceResponse(
            $endpointData,
            $resourceModel,
            $returnType
        );
    }

    public function getApiResourceResponseFromAttribute(ExtractedEndpointData $endpointData, string $returnType, ApiResource $apiResource): ?array
    {
        $collection = in_array(ResourceCollection::class, class_parents($returnType) ?: []);

        return $this->getApiResourceResponse(
            $endpointData,
            $apiResource->resourceModel,
            $apiResource->resourceClass ?? $returnType,
            $apiResource->factoryStates,
            $apiResource->relations,
            $returnType === AnonymousResourceCollection::class,
            $collection,
            $apiResource->perPage,
            $apiResource->simplePaginator,
            $apiResource->statusCode,
        );
    }

    protected function getApiResourceResponse(
        ExtractedEndpointData $endpointData,
        string $resourceModel,
        string $apiResourceClass,
        array $factoryStates = [],
        array $relations = [],
        bool $anonymous = false,
        bool $collection = false,
        ?int $perPage = null,
        bool $simplePaginator = false,
        int $statusCode = 200
    ): ?array {
        $modelInstance = $this->instantiateApiResourceModel($resourceModel, $factoryStates, $relations);

        if (! $collection) {
            /** @var JsonResource $resource */
            $resource = new $apiResourceClass($modelInstance);

            return $this->getResourceResponse($endpointData, $resource, $statusCode);
        }

        $models = collect([$modelInstance, $this->instantiateApiResourceModel($resourceModel, $factoryStates, $relations)]);

        if ($perPage && $simplePaginator) {
            $paginator = new Paginator($models, $perPage);
            $list = $paginator;
        } elseif ($perPage) {
            $paginator = new LengthAwarePaginator(
                $models->slice(0, $perPage),
                count($models),
                $perPage
            );
            $list = $paginator;
        } else {
            $list = $models;
        }

        if ($anonymous) {
            $resource = $apiResourceClass::collection($list);

            return $this->getResourceResponse($endpointData, $resource, $statusCode);
        }

        /** @var \Illuminate\Http\Resources\Json\ResourceCollection $resource */
        $resource = new $apiResourceClass($list);

        return $this->getResourceResponse($endpointData, $resource, $statusCode);
    }

    protected function getResourceResponse(ExtractedEndpointData $endpointData, JsonResource $resource, int $statusCode): array
    {
        $uri = Utils::getUrlWithBoundParameters($endpointData->route?->uri() ?? '', $endpointData->cleanUrlParameters);
        $method = $endpointData->route?->methods()[0];

        $request = Request::create($uri, $method);
        $request->headers->add(['Accept' => 'application/json']);

        app()->bind('request', function () use ($request) {
            return $request;
        });

        $route = $endpointData->route;
        /** @var Response $response */
        $response = $resource->toResponse(
            $request->setRouteResolver(function () use ($route) {
                return $route;
            })
        );

        return [
            [
                'status' => $statusCode,
                'content' => $response->getContent(),
            ],
        ];
    }

    protected function instantiateApiResourceModel(string $type, array $factoryStates = [], array $relations = []): object
    {
        try {
            // Try Eloquent model factory
            $factory = Utils::getModelFactory($type, $factoryStates, $relations);

            try {
                return $factory->create()->load($relations);
            } catch (Throwable $e) {
                c::warn("Eloquent model factory failed to create {$type}; trying to make it.");
                e::dumpExceptionIfVerbose($e, true);

                return $factory->make();
            }
        } catch (Throwable $e) {
            c::warn("Eloquent model factory failed to instantiate {$type}; trying to fetch from database.");
            e::dumpExceptionIfVerbose($e, true);

            $instance = new $type();
            if ($instance instanceof Model) {
                try {
                    // We can't use a factory but can try to get one from the database
                    $firstInstance = $type::with($relations)->first();
                    if ($firstInstance) {
                        return $firstInstance;
                    }
                } catch (Throwable $e) {
                    // okay, we'll stick with `new`
                    c::warn("Failed to fetch first {$type} from database; using `new` to instantiate.");
                    e::dumpExceptionIfVerbose($e);
                }
            }
        }

        return $instance;
    }
}
