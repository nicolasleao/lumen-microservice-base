# Laravel/Lumen-framework microservice base

This project includes extendable classes that implement Multi-tenancy, CRUD operations, ordering, filtering and validation using the Service and Repository patterns

## Installation
Add the following lines to your ```composer.json``` file:

```json
"require": {
    "nicolasleao/lumen-microservice-base": "^1.0.0"
},
"repositories": [
    {
        "type": "vcs",
        "url": "https://github.com/nicolasleao/lumen-microservice-base"
    }
]
```

and run ```composer install``` to install the package from github.

## Basic Concepts

#### Multi-tenancy (Optional)

This project provides a Model, 2 Commands, and a Middleware that can be used to implement
multi-tenancy using one schema per tenant on PostgreSQL.

1. Models/Tenant.php
    - Associates a domain with a schema_name, and is used by the middleware to connect to the correct tenant for the current request.
2. Middleware/TenancyMiddleware.php
    - Tries to find a matching Tenant model by the request's domain, and caches the results for one day, to minimize recurrent database queries and increase response time
3. Commands/LandlordMigrateCommand.php
    - Creates the landlord schema and tenants table, called using ```php artisan landlord:migrate``` or ```php artisan landlord:migrate --fresh```
4. Commands/TenantsMigrateCommand.php
    - Creates a schema for every tenant inserted in the landlord.tenants table, called using ```php artisan tenants:migrate``` or ```php artisan tenants:migrate --fresh```

To use this implementation of multi-tenancy in your application, you must first register the Migration Commands in your ```app/Console/Kernel.php``` file:

```
DB_CONNECTION=tenant
DB_HOST=localhost
DB_PORT=5432
DB_DATABASE=testing
DB_USERNAME=homestead
DB_PASSWORD=secret

LANDLORD_DB_CONNECTION=landlord
LANDLORD_DB_HOST=localhost
LANDLORD_DB_PORT=5432
LANDLORD_DB_DATABASE=testing
LANDLORD_DB_SCHEMA=landlord
LANDLORD_DB_USERNAME=homestead
LANDLORD_DB_PASSWORD=secret
```

```php
/**
 * The Artisan commands provided by your application.
 *
 * @var array
 */
protected $commands = [
    LumenMicroservice\Commands\LandlordMigrateCommand::class,
    LumenMicroservice\Commands\TenantsMigrateCommand::class,
];
```

Then, you'll need to run the initial migrations for the landlord schema, using:
<br>``` php artisan landlord:migrate --fresh```<br>
 and populate the landlord.tenants table with the domains and schema
names you want to use (Using your favourite PostgreSQL client or the cli). After that, you can run the command:
<br>``` php artisan tenants:migrate```<br>
 and it will create the schemas you defined and run all migrations in your ```database/migrations``` folder for every tenant.<br><br>

Then, you can enable the TenancyMiddleware for your application or for specific routes by adding the following lines to your ```bootstrap/app.php``` file.

```php
// Use the multi-tenancy middleware in the whole application
$app->middleware([
    LumenMicroservice\Middleware\TenancyMiddleware::class
]);

// Expose the multi-tenancy middleware to your routes/web.php file and apply to specific routes
$app->routeMiddleware([
    'tenancy' => LumenMicroservice\Middleware\TenancyMiddleware::class
]);
```

And you're done! Now every domain registered in your tenants table on the landlord database will have a separate schema generated on your application, and every request will access the correct schema based on the request's domain.

### Controllers
#### Controllers are components that have the soul purpose of getting a request from the client and returning a response from the server, and it does that using methods present on the service and repository classes related to that controller.
All controllers should be stored in ```app/Http/Controllers```.<br>

That means that for every controller, you should create a Service and a Repository to handle the business logic.

For example: If you want to create an ArticleController, you'll also create an ArticleService and an ArticleRepository.

By doing that, your ArticleController will automatically have CRUD operations inherited from the ```LumenMicroservice\Classes\Controller``` class with the following methods:

```php
public function showAll(Request $request)
public function show($id)
public function search(string $q, Request $request)
public function store(Request $request)
public function update($id, Request $request)
public function destroy($id)
public function restore($id)
```

The basic anatomy of a controller is as follows:
```php
<?php

namespace App\Http\Controllers;

use LumenMicroservice\Classes\Controller;
use App\Services\ArticleService; // Will be created in the following section

class ArticleController extends Controller
{
    /**
     * Create a new controller instance and inject the $service dependency.
     * @return void
     */
    public function __construct(ArticleService $service) {
        $this->service = $service;
    }

    // Add your own controller methods here
}

```

### Services
#### Services are responsible for all the validation and some error handling on your requests.
All services should be stored in ```app/Services```.<br>

By extending ```LumenMicroservice\Classes\Service``` your service inherits the following methods:

```php
/**
 * Create $orderBy array from querystring and pass it to the repository findAll() method
 * treating any QueryExceptions in the process.
 * @param Request $request
 * @return string (json)
 */
public function showAll(Request $request)

/**
 * Call the repository findOne() method, treating any QueryExceptions in the process.
 * @param string $id
 * @return string (json)
 */
public function show($id)

/**
 * Create $fields and $orderBy arrays from querystring (or search in the name field as a default if none is provided) and pass it to the 
 * repository search() method, treating any QueryExceptions in the process.
 * @param Request $request
 * @param string $q
 * @return string (json)
 */
public function search(string $q, Request $request)

/**
 * Validate the request and pass it's data to the repository create() method, treating any QueryException
 * in the process.
 * @param Request $request
 * @return string (json)
 */
public function store(Request $request)

/**
 * Validate the request and pass it's data (partials allowed) to the repository update() method, treating any
 * QueryExceptions in the process.
 * @param string $id
 * @param Request $request
 * @return string (json)
 */
public function update($id, Request $request)

/**
 * Call the repository delete() method, treating any QueryExceptions in the process.
 * @param string $id
 * @return string (json)
 */
public function destroy($id);

/**
 * Call the repository restore() method, treating any QueryExceptions in the process.
 * @param string $id
 * @return string (json)
 */
public function restore($id);
```

The anatomy of a service is as follows (note the validateInsert() and validateUpdate() methods).
```php
<?php

namespace App\Services;

use LumenMicroservice\Classes\Service;
use Illuminate\Support\Facades\Validator;
use App\Repositories\ArticleRepository;

class ArticleService extends Service
{
    /**
     * Create a new service instance and inject the $repo dependency.
     * @return void
     */
    public function __construct(ArticleRepository $repo) {
        $this->repo = $repo;
    }

    //// THESE VALIDATION METHODS ARE OPTIONAL
    protected function validateInsert(Request $request) {
        $validator = Validator::make($request, [
            'title'=>'required'
            'author'=>'required'
            'likes'=>'numeric'
        ]);

        if($validator->errors()) {
            return $validator->errors();
        }
        return [];
    }

    protected function validateUpdate(Request $request) {
        $validator = Validator::make($request, [
            'likes'=>'numeric'
        ]);

        if($validator->errors()) {
            return $validator->errors();
        }
        return [];
    }

    // Add your own service methods here
}
```

Warning: If you do not implement these variation methods, your route will have no validation, and every input will be considered valid, possibly causing errors in edge-cases.

### Repositories
#### Repositories are responsible for contacting the database and returning the data in the order and with the filters that were requested by the client, so all queries are done here.
All repositories should be stored in the ```app/Repositories``` folder.


By extending ```LumenMicroservice\Classes\Repository``` your service inherits the following methods:

```php
public function create(array $data)
public function findOne($id)
public function findAll(array $orderBy, array $filters)
public function search(string $terms, array $fields, array $orderBy, array $filters)
public function update($id, array $data)
public function delete(array $ids)
public function restore(array $ids)
```

The anatomy of a repository is as follows:

```php
<?php

namespace App\Repositories;

use LumenMicroservice\Classes\Repository;
use App\Models\Article;

/**
 * Class ArticleRepository
 * @package App\Repositories
 */
class ArticleRepository extends Repository
{
    /*
     * You can inject any Model from your project and the available methods will adapt
     * to make queries for that model
     */
    public function __construct(Article $model) {
        $this->model = $model;
    }
}
```

### Routes (web.php)
Once all that is configured, you can map routes to the methods in the controller class, for instance:

```php
// Article Model Routes
$router->group(['prefix' => 'articles'], function () use ($router) {
    $router->get('/', 'ArticleController@showAll');
    $router->get('/{id}', 'ArticleController@show');
    $router->get('/search/{q}', 'ArticleController@search');
    $router->post('/', 'ArticleController@store');
    $router->put('/{id}', 'ArticleController@update');
    $router->delete('/{id}', 'ArticleController@destroy');
    $router->patch('/{id}/restore', 'ArticleController@restore');
});
``` 

### Ordering & Filtering
All the ordering and filtering logic is done in the applyOrdersAndFilters() method on the repository class, and it works with querystring parameters.

#### Ordering
When making a request to a listing endpoint, like showAll or search, you can pass and orderBy parameter, with many fields separated by comma on the querystring telling the order of the resulting json array, for example:

```GET my-lumen-application.local/articles?orderBy=id,-name```

NOTE: adding the character '-' before the field name, will cuase decrescent ordering, so<br>
```?orderBy=-id```<br>
is equivalent to<br>
```ORDER BY id DESC```<br>
on the database.

#### Filtering
All requests to listing endpoints, like showAll or search, can be filtered using the following parameter on the querystring:<br>
```GET my-lumen-application.local/articles?filters=id|>4,likes|<=20,author|Marcus```<br>

This query will return articles where the id is > 4, that have 20 or less likes, and were posted by the author Marcus.


