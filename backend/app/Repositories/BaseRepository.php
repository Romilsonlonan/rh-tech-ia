<?php

namespace App\Repositories;

use App\Services\BaseService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;

abstract class BaseRepository extends BaseService implements BaseRepositoryInterface
{
    protected Model $model;

    public function paginate(int $perPage = 15)
    {
        return $this->model->paginate($perPage);
    }

    public function findBy(string $field, mixed $value): ?Model
    {
        return $this->model->where($field, $value)->first();
    }

    public function where(array $conditions): Collection
    {
        $query = $this->model->query();
        foreach ($conditions as $field => $value) {
            $query->where($field, $value);
        }
        return $query->get();
    }
}
