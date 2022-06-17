<?php

declare(strict_types=1);

namespace Magwel\ScribeAnnotations\Tests\Fixtures;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TestUser>
 */
class TestUserFactory extends Factory
{
    protected $model = TestUser::class;

    public function randomState(): self
    {
        return $this->state(function () {
            return [
                'randomState' => true,
            ];
        });
    }

    public function withChild(): self
    {
        return $this->afterMaking(function (TestUser $testUser) {
            $child = TestUser::factory()->makeOne([
                'parent_id' => $testUser->id,
            ]);

            $testUser->setRelation('children', [$child]);
        });
    }

    public function definition(): array
    {
        return [
            'first_name' => 'Tested',
            'last_name' => 'Again',
            'email' => 'a@b.com',
        ];
    }
}
