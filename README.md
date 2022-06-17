# Scribe Annotation

The amazing [scribe](scribe.knuckles.wtf) package can generate API documentation automatically from your Laravel project. 

This package adds PHP 8 annotations so the IDE is aware of class names. 
It can also automatically determine related model names from the mixin annotation in your JsonResource.

# Annotation's

### Anonymous resource collections
```php
<?php

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Magwel\ScribeAnnotations\Attributes\ApiResource;
 
class UserController
{
    #[ApiResource(User::class, UserResource::class)]
    public function __invoke(): AnonymousResourceCollection
    {
        $users = User::all();
        
        return UserResource::collection($users);
    }
}
```

### Custom status code
```php
<?php

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Magwel\ScribeAnnotations\Attributes\ApiResource;
 
class UserController
{
    #[ApiResource(User::class, UserResource::class, statusCode: 201)]
    public function __invoke(): AnonymousResourceCollection
    {
        $users = User::all();
        
        return UserResource::collection($users);
    }
}
```
### Factory states

```php
<?php

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

    public function definition(): array
    {
        return [
            'first_name' => 'Tested',
            'last_name' => 'Again',
            'email' => 'a@b.com',
        ];
    }
}
```

```php
<?php

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Magwel\ScribeAnnotations\Attributes\ApiResource;
 
class UserController
{
    #[ApiResource(User::class, UserResource::class, factoryStates: ['randomState'])]
    public function __invoke(): AnonymousResourceCollection
    {
        $users = User::all();
        
        return UserResource::collection($users);
    }
}
```

## Automatically determine resource model from resource mixin

```php
/**
 * @mixin \Magwel\ScribeAnnotations\Tests\Fixtures\TestUser
 */
class UserResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->first_name . ' ' . $this->last_name,
        ];
    }
}
```

```php
<?php

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Magwel\ScribeAnnotations\Attributes\ApiResource;
 
class UserController
{
    public function __invoke(): UserResource
    {
        $users = User::first();
        
        return new UserResource($users);
    }
}
```
