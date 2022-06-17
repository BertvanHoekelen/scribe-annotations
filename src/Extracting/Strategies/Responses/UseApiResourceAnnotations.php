<?php

declare(strict_types=1);

namespace Magwel\ScribeAnnotations\Extracting\Strategies\Responses;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
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
use ReflectionClass;
use ReflectionMethod;
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

        if (! $apiResourceAttribute) {
            return null;
        }

        $this->startDbTransaction();

        try {
            return $this->getApiResourceResponse($endpointData, $method, $apiResourceAttribute->newInstance());
        } catch (Exception $e) {
            c::warn('Exception thrown when fetching Eloquent API resource response for ' . $endpointData->name());
            e::dumpExceptionIfVerbose($e);

            return null;
        } finally {
            $this->endDbTransaction();
        }
    }

    public function getApiResourceResponse(ExtractedEndpointData $endpointData, ReflectionMethod $method, ApiResource $apiResource): ?array
    {
        /** @var class-string<JsonResource> $apiResourceClass */
        $apiResourceClass = $method->getReturnType()?->getName() ?? $apiResource->resourceClass; /** @phpstan-ignore-line */
        $resource = new ReflectionClass($apiResourceClass);

        $modelInstance = $this->instantiateApiResourceModel($apiResource->resourceModel, $apiResource->factoryStates, $apiResource->relations);

        if (! $resource->isSubclassOf(ResourceCollection::class)) {
            /** @var JsonResource $resource */
            $resource = new $apiResourceClass($modelInstance);

            return $this->getResourceResponse($endpointData, $resource, $apiResource);
        }

        $models = collect([$modelInstance, $this->instantiateApiResourceModel($apiResource->resourceModel, $apiResource->factoryStates, $apiResource->relations)]);

        if ($apiResource->perPage && $apiResource->simplePaginator) {
            $paginator = new Paginator($models, $apiResource->perPage);
            $list = $paginator;
        } elseif ($apiResource->perPage) {
            $paginator = new LengthAwarePaginator(
            // For some reason, the LengthAware paginator needs only first page items to work correctly
                $models->slice(0, $apiResource->perPage),
                count($models),
                $apiResource->perPage
            );
            $list = $paginator;
        } else {
            $list = $models;
        }

        if ($apiResource->resourceClass) {
            $resource = $apiResource->resourceClass::collection($list);

            return $this->getResourceResponse($endpointData, $resource, $apiResource);
        }

        $resource = new $apiResourceClass($list);

        return $this->getResourceResponse($endpointData, $resource, $apiResource);
    }

    protected function getResourceResponse(ExtractedEndpointData $endpointData, JsonResource $resource, ApiResource $apiResource): array
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
                'status' => $apiResource->statusCode,
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
