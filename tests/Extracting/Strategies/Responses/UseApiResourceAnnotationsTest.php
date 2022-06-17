<?php

declare(strict_types=1);

namespace Magwel\ScribeAnnotations\Tests\Extracting\Strategies\Responses;

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Illuminate\Routing\Route;
use Knuckles\Camel\Extraction\ExtractedEndpointData;
use Knuckles\Scribe\Tools\DocumentationConfig;
use Magwel\ScribeAnnotations\Extracting\Strategies\Responses\UseApiResourceAnnotations;
use Magwel\ScribeAnnotations\Tests\BaseLaravelTest;
use Magwel\ScribeAnnotations\Tests\Fixtures\TestController;

class UseApiResourceAnnotationsTest extends BaseLaravelTest
{
    use ArraySubsetAsserts;

    /**
     * @test
     */
    public function can_parse_api_resource_annotations(): void
    {
        $results = $this->getResultsForRoute([TestController::class, 'withEloquentApiResourceAnnotation']);

        self::assertArraySubset([
            [
                'status' => 200,
                'content' => json_encode([
                    'data' => [
                        'id' => 1,
                        'name' => 'Tested Again',
                        'email' => 'a@b.com',
                    ],
                ]),
            ],
        ], $results);
    }

    /**
     * @test
     */
    public function can_parse_api_resource_annotations_with_model_factory_states(): void
    {
        $results = $this->getResultsForRoute([TestController::class, 'withEloquentApiResourceAnnotationWithStates']);

        self::assertArraySubset([
            [
                'status' => 201,
                'content' => json_encode([
                    'data' => [
                        'id' => 1,
                        'name' => 'Tested Again',
                        'email' => 'a@b.com',
                        'random-state' => true,
                    ],
                ]),
            ],
        ], $results);
    }

    /**
     * @test
     */
    public function can_parse_api_resource_annotations_with_model_relations(): void
    {
        $results = $this->getResultsForRoute([TestController::class, 'withEloquentApiResourceAnnotationWithChildren']);

        self::assertArraySubset([
            [
                'status' => 200,
                'content' => json_encode([
                    'data' => [
                        'id' => 1,
                        'name' => 'Tested Again',
                        'email' => 'a@b.com',
                        'children' => [
                            [
                                'id' => 2,
                                'name' => 'Tested Again',
                                'email' => 'a@b.com',
                            ],
                        ],
                    ],
                ]),
            ],
        ], $results);
    }

    /**
     * @test
     */
    public function can_parse_api_resource_collection_annotation(): void
    {
        $results = $this->getResultsForRoute([TestController::class, 'withEloquentApiResourceCollectionAnnotation']);

        self::assertArraySubset([
            [
                'status' => 200,
                'content' => json_encode([
                    'data' => [
                        [
                            'id' => 1,
                            'name' => 'Tested Again',
                            'email' => 'a@b.com',
                        ],
                        [
                            'id' => 2,
                            'name' => 'Tested Again',
                            'email' => 'a@b.com',
                        ],
                    ],
                ]),
            ],
        ], $results);
    }

    /**
     * @test
     */
    public function can_parse_api_resource_collection_class_annotation(): void
    {
        $results = $this->getResultsForRoute([TestController::class, 'withEloquentApiResourceCollectionClassAnnotation']);

        self::assertArraySubset([
            [
                'status' => 200,
                'content' => json_encode([
                    'data' => [
                        [
                            'id' => 1,
                            'name' => 'Tested Again',
                            'email' => 'a@b.com',
                        ],
                        [
                            'id' => 2,
                            'name' => 'Tested Again',
                            'email' => 'a@b.com',
                        ],
                    ],
                    'links' => [
                        'self' => 'link-value',
                    ],
                ]),
            ],
        ], $results);
    }

    /**
     * @test
     */
    public function can_parse_api_resource_collection_annotations_with_collection_class_and_simple_pagination(): void
    {
        $results = $this->getResultsForRoute([TestController::class, 'withEloquentApiResourceCollectionClassWithSimplePaginationAnnotation']);

        self::assertArraySubset([
            [
                'status' => 200,
                'content' => json_encode([
                    'data' => [
                        [
                            'id' => 1,
                            'name' => 'Tested Again',
                            'email' => 'a@b.com',
                        ],
                    ],
                    'links' => [
                        'self' => 'link-value',
                        'first' => '/?page=1',
                        'last' => null,
                        'prev' => null,
                        'next' => '/?page=2',
                    ],
                    'meta' => [
                        'current_page' => 1,
                        'from' => 1,
                        'path' => '/',
                        'per_page' => 1,
                        'to' => 1,
                    ],
                ]),
            ],
        ], $results);
    }

    /**
     * @test
     */
    public function can_parse_api_resource_collection_annotations_with_collection_class_and_pagination(): void
    {
        $results = $this->getResultsForRoute([TestController::class, 'withEloquentApiResourceCollectionClassWithPaginationAnnotation']);

        self::assertArraySubset([
            [
                'status' => 200,
                'content' => json_encode([
                    'data' => [
                        [
                            'id' => 1,
                            'name' => 'Tested Again',
                            'email' => 'a@b.com',
                        ],
                    ],
                    'links' => [
                        'self' => 'link-value',
                        'first' => '/?page=1',
                        'last' => '/?page=2',
                        'prev' => null,
                        'next' => '/?page=2',
                    ],
                    'meta' => [
                        'current_page' => 1,
                        'from' => 1,
                        'last_page' => 2,
                        'links' => [
                            [
                                'url' => null,
                                'label' => '&laquo; Previous',
                                'active' => false,
                            ],
                            [
                                'url' => '/?page=1',
                                'label' => '1',
                                'active' => true,
                            ],
                            [
                                'url' => '/?page=2',
                                'label' => '2',
                                'active' => false,
                            ],
                            [
                                'url' => '/?page=2',
                                'label' => 'Next &raquo;',
                                'active' => false,
                            ],
                        ],
                        'path' => '/',
                        'per_page' => 1,
                        'to' => 1,
                        'total' => 2,
                    ],
                ]),
            ],
        ], $results);
    }

    private function getResultsForRoute(array $route): array
    {
        $config = new DocumentationConfig([]);

        $route = new Route(['POST'], '/somethingRandom', ['uses' => $route]);

        $strategy = new UseApiResourceAnnotations($config);
        $results = $strategy(ExtractedEndpointData::fromRoute($route), []);

        return $results ?? [];
    }
}
