<?php

namespace Ahrmerd\RestProvider;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Resources\Json\JsonResource;

class RestProviderControllerActions
{
    /**
     * The repository model.
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $model;


    /**
     * The repository model resource.
     *
     * @var null|JsonResource
     */
    protected $resource;

    /**
     * The query builder.
     * @var \Illuminate\Database\Eloquent\Builder
     */
    protected $query;


    public function setLimit(Builder $query): Builder
    {
        return $query->when(request()->has('limit'), fn(Builder $query) => $query->limit(request()->query('limit')));
    }

    public function setLimitAndOffset(Builder $query): Builder
    {
        return $this->setOffset($this->setLimit($query));
    }

    public function setOffset(Builder $query, $from = 0): Builder
    {
        return $query->when(request()->has('offset'), fn(Builder $query) => $query->offset(request()->query('limit')));
    }


    public function __construct($model, protected $includes = [], protected $filters=[], protected $sorts=[])
    {
        $this->makeModel($model);
        $this->makeResource($model);
    }

    

    /**
     * @return Model|mixed
     * @throws \Exception
     */
    public function makeModel($model)
    {
        $model = app()->make($model);

        if (!$model instanceof Model) {
            throw new \Exception("Class {$model} must be an instance of " . Model::class);
        }

        return $this->model = $model;
    }

    public function makeResource($model)
    {
        $namespace = 'App\Http\Resources';
        try {
            $resourceClass = $namespace . '\\' . class_basename($model) . 'Resource';
            $resourceInstance = new $resourceClass([]);
            if ($resourceInstance instanceof JsonResource) {
                return $this->resource = $resourceClass;
            }
        } catch (\Throwable $th) {
            // throw $th;
        }
        return $this->resource = $this->newResource([]);
    }

        /**
     * @return JsonResource
     */
    public function newResource($resource){
        return new class($resource) extends JsonResource {
            public function __construct($resource)
            {
                parent::__construct($resource);
            }
        };
    }

    /**
     * Create a new model record in the database.
     *
     * @param array $data
     *
     * @return JsonResource
     */
    public function create(array $data)
    {
        return $this->newResource($this->model->create($data));
    }

    /**
     * Create one or more new model records in the database.
     *
     * @param array $data
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|Collection
     */
    public function createMultiple(array $data)
    {
        $models = new Collection();
        // return new BaseResource($this->model->create($data));

        foreach ($data as $d) {
            $models->push($this->create($d));
        }
        // return $models;
        return JsonResource::collection($models);
        // return BaseResource::collection($models);
    }

    /**
     * Delete the specified model record from the database.
     *
     * @param $id
     *
     * @return bool|null
     * @throws \Exception
     */
    public function deleteById($id): bool
    {
        return $this->model->newQuery()->findOrFail($id)->delete();
    }

    /**
     * Delete multiple records.
     *
     * @param array $ids
     *
     * @return int
     */
    public function deleteMultipleById(array $ids): int
    {
        return $this->model->destroy($ids);
    }

    /**
     * Get the first specified model record from the database.
     *
     * @param array $columns
     *
     * @return JsonResource
     */
    public function first(array $columns = ['*'])
    {
        $model = $this->query->firstOrFail($columns);
        return $this->newResource($model);
    }

    /**
     * Get all the specified model records in the database.
     *
     * @param array $columns
     *
     * @return Collection|static[]
     */
    public function get(array $columns = ['*'])
    {
        $models = $this->query->get($columns);

        return $models;
    }

    public function getRequestedIncludes()
    {
        // $includes = [];
        // $allowedIncludes = $this->getIncludes();
        if (request()->has('include')) {
            $requestedIncludes = explode(',', request()->input('include'));
            foreach ($requestedIncludes as $relationship) {
                if (in_array(strtolower($relationship), $this->includes)) {
                    array_push($includes, $relationship);
                }
            }
        }

        return $includes;
    }

    public function appendIncludes(Builder|Model $query)
    {
        if (request()->has('include')) {
            $includes = $this->getRequestedIncludes();
            return $query instanceof Model ? $query->load($includes) : $query->with($this->getRequestedIncludes());
        } else {
            return $query;
        }

        // dump($query->getQuery()->get());
        // if ($query instanceof Model) {
        // return $query->load();
        // } else {
        // # code...
        // }

        // return request()->has('include')  ? $query->with($this->getRequestedIncludes()) : $query;
    }

    public function getById($id, array $columns = ['*'])
    {
        return new $this->resource($this->appendIncludes($this->model->newQuery())->findOrFail($id));
    }

    public function addIncludes(Model $model)
    {
        return new $this->resource($this->appendIncludes($model));
    }


    public function index(Builder $query = null)
    {

        $total = $this->model->count();
        $query = $query ? $this->setLimitAndOffset($query) : $this->setLimitAndOffset($this->model->query());
        $query = \Spatie\QueryBuilder\QueryBuilder::for ($query)
            ->allowedSorts([...$this->sorts, 'id', 'created_at'])
            ->allowedIncludes($this->includes)
            ->allowedFilters($this->filters);
        $models = request()->has('page') ? $query->paginate() : $query->get();
        if ($this->resource) {
            return $this->resource::collection(($models))->additional(['Total-Count' => $total]);
        } else {
            return ['data' => $models, 'Total-Count' => $total];
        }
    }

}