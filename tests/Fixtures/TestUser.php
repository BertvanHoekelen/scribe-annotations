<?php

declare(strict_types=1);

namespace Magwel\ScribeAnnotations\Tests\Fixtures;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int                                                                                          $id
 * @property string                                                                                       $first_name
 * @property string                                                                                       $last_name
 * @property string                                                                                       $email
 * @property \Illuminate\Database\Eloquent\Collection|\Magwel\ScribeAnnotations\Tests\Fixtures\TestUser[] $children
 * @property string                                                                                       $randomState
 */
class TestUser extends Model
{
    use HasFactory;

    protected static function newFactory(): TestUserFactory
    {
        return new TestUserFactory();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany<\Magwel\ScribeAnnotations\Tests\Fixtures\TestUser>
     */
    public function children(): HasMany
    {
        return $this->hasMany(TestUser::class, 'parent_id');
    }
}
