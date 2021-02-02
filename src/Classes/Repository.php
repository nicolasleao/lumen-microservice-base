<?php

namespace Nicolasleao\BaseService\Classes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

/**
 * Class Repository
 * @package App\Repositories
 */
class Repository
{
    /**
     * var Model
     */
    protected Model $model;
    /**
     * @param Model $model
     */
    public function __construct(Model $model) {
        $this->model = $model;
    }

    /**
     * INSERT a record on the database
     * @param array $data
     * @return array
     */
    public function create(array $data) {
        return $this->model::create($data)->toArray();
    }

    /**
     * SELECT a record on the database or returns an error
     * @param int $id
     * @return array
     */
    public function findOne($id) {
        return $this->model::findOrFail($id)->toArray();   
    }

    /**
     * SELECT all records on the database with pagination
     * @param array $orderBy
     * @return array
     */
    public function findAll(array $orderBy, array $filters) {
        $results = $this->model::query();

        $results = $this->applyOrdersAndFilters($results, $orderBy, $filters);

        return $results->paginate()->toArray();
    }

    /**
     * SELECT many records on the database based on a search string with pagination
     * @param string $terms
     * @param array $fields
     * @param array $orderBy
     * @return array
     */
    public function search(string $terms, array $fields, array $orderBy, array $filters) {
        $results = $this->model::where($fields[0], 'like', '%' . $terms . '%');

        if(count($fields) > 1) {
            foreach($fields as $field) {
                $results->orWhere($field, 'like', '%' . $terms . '%');
            }
        }

        $results = $this->applyOrdersAndFilters($results, $orderBy);

        return $results->paginate()->toArray();
    }

    /**
     * UPDATE a record on the database based on an array of ids
     * @param array $id
     * @param array $data
     * @return object
     */
    public function update($id, array $data) {
        $this->model::where('id', $id)->update($data);
        return $this->model::findOrFail($id);
    }

    /**
     * ARCHIVE many records on the database based on an array of ids
     * @param array $ids
     * @return string
     */
    public function delete(array $ids) {
        return $this->model::whereIn('id', $ids)->delete() ? 'success' : 'failure';
    }

    /**
     * UNARCHIVE many records on the database based on an array of ids
     * @param array $ids
     * @return bool
     */
    public function restore(array $ids) {
        return $this->model::whereIn('id', $ids)->restore() ? 'success' : 'failure';
    }

    /**
     * Apply orderBy and Filter Arrays to the query and return the querybuilder instance
     * with these properties applied.
     * @param Eloquent\Query $results
     * @param array $orderBy
     * @return bool
     */
    protected function applyOrdersAndFilters($results, $orderBy = [], $filters = [])
    {
        /**
         * Get $orderBy array and apply it to the query, '-' before the field indicates
         * that the results should be ordered in descending order.
         *
         * Input example: ?orderBy=price,-id
         */
        foreach($orderBy as $key) {
            try {
                if(strstr($key, '-')) {
                    $key = substr($key, 1);
                    $results = $results->orderBy($key, 'desc');
                }
                else {
                    $results = $results->orderBy($key);
                }
            }
            catch (Exception $e) {
                throw ValidationException::withMessages([$key => 'Invalid syntax for orderBy parameter']);
            }
        }

        /**
         * Get $filters array and apply it to the query, '<' or '>' before the
         * value indicates the comparison operator to be used.
         */
        foreach($filters as $filter) {
            /**
             * $filters is a string array, so we need to separate the key from the value using
             * the '|' wildcard character for each item in the array, wielding the
             * result: Key = key_value[0], Value = key_value[1]
             *
             * Input example: &filters=products.created_at|>2021-01-12T20:42:47.000000Z,products.price|>5000
             */ 
            try {
                $key_value = explode('|', $filter);

                // Only try to do the operations a key and a value were provided
                if(count($key_value) > 1) {

                    if(strstr($key_value[0], 'deleted_at')) {
                        $results = $results->withTrashed();
                    }

                    // Check for '<=' or '>=' operators before the value, and apply that to the query
                    if(strstr($key_value[1], '<=') || strstr($key_value[1], '>=')) {
                        $value = substr($key_value[1], 2);

                        $operator = substr($key_value[1], 0, 2);
                        $results = $results->where($key_value[0], $operator, $value);
                    }
                    /**
                     * If those operators were not found, check for '<>' operator before the value
                     * and apply that to the query
                     */
                    else if(strstr($key_value[1], '!')) {
                        $value = substr($key_value[1], 1);
                        if($value === 'null') {
                            $results = $results->whereNotNull($key_value[0]);
                        }
                        else {
                            $results = $results->where($key_value[0], '<>', $key_value[1]);
                        }
                    }
                    /**
                     * If that operator was not found, check for '<' or '>' operators before the value
                     * and apply that to the query
                     */
                    else if(strstr($key_value[1], '<') || strstr($key_value[1], '>')) {
                        $value = substr($key_value[1], 1);
                        $operator = substr($key_value[1], 0, 1);

                        $results = $results->where($key_value[0], $operator, $value);
                    }
                    // If none of these operators were found, use the '=' operator.
                    else {
                        if($key_value[1] === 'null') {
                            $results = $results->whereNull($key_value[0]);
                        }
                        else {
                            $results = $results->where($key_value[0], $key_value[1]);
                        }
                    }
                }
            }   
            catch (Exception $e) {
                throw ValidationException::withMessages([$filter => 'Invalid syntax for filter parameter']);
            }
        }

        return $results;
    }
}

