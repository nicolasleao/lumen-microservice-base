<?php

namespace LumenMicroservice\Classes;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use App\Models\Product;

class Service
{
	/**
     * Repository to interact with the Product Model.
     * @var Repository
     */
    protected Repository $repo;

    /**
     * Create a new controller instance and inject the $repo dependency.
     * @return void
     */
    public function __construct(Repository $repo) {
        $this->repo = $repo;
    }

    /**
     * This method validates the request for the insertion of resources, and should be
     * overriden by any classes that extend Service.
     * @param Request $request
     * @return array $errors
     */
    protected function validateInsert($input) {
        return ['errors' => []];
    }

    /**
     * This method validates the request for the alteration of resources, and should be
     * overriden by any classes that extend Service.
     * @param Request $request
     * @return array $errors
     */
    protected function validateUpdate($input) {
        return ['errors' => []];
    }

    protected function hasInvalidInput($input, $validations) : array|bool {
        $validator = Validator::make($input, $validations);

        if($validator->fails()) {
            return [
                'message' => 'Validation errors',
                'errors' =>  $validator->errors(), 
                'status' => false
            ];
        }

        return false;
    }

    /**
     * Create $orderBy array from querystring and pass it to the repository findAll() method
     * treating any QueryExceptions in the process.
     * @param Request $request
     * @return string (json)
     */
	public function showAll(Request $request) {
        // Try to contact the repository and execute request
        try {

            /**
             * If $orderBy is present on the querystring, split the string
             * and pass it as an array to the repository method findAll(),
             * do the same for $filters
             */
            $orderBy = $request->input('orderBy');
            $filters = $request->input('filters');
            $orderBy ? $orderBy = explode(',', $orderBy) : $orderBy = [];
            $filters ? $filters = explode(',', $filters) : $filters = [];

            return response()->json(
                $this->repo->findAll($orderBy, $filters)
            );
        }
        // Catch any query exceptions and return a JSON formatted response (other exceptions will return text/html)
        catch(QueryException $exception) {
            return response()->json(['error'=>'Database error', 'errorInfo'=>$exception->errorInfo], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Call the repository show() method, treating any QueryExceptions in the process.
     * @param string $id
     * @return string (json)
     */
    public function show($id) {
        // Try to contact the repository and execute request
        try {
            return response()->json(
                $this->repo->findOne($id)
            );
        }
        // Catch any query exceptions and return a JSON formatted response (other exceptions will return text/html)
        catch(QueryException $exception) {
            return response()->json(['error'=>'Database error', 'errorInfo'=>$exception->errorInfo], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Create $fields and $orderBy arrays from querystring (or using a default if none is provided) and pass it to the 
     * repository search() method, treating any QueryExceptions in the process.
     * @param Request $request
     * @param string $q
     * @return string (json)
     */
    public function search(string $q, Request $request) {
        /*
         * Ternary operator to get the 'fields' parameter from $request and convert to an array
         * or default to ['name'] if parameter is not present.
         */
        $fields = $request->has('fields') ? explode(',', $request->input('fields')) : ['name'] ;

        // Try to contact the repository and execute request
        try {
            /**
             * If $orderBy is present on the querystring, split the string
             * and pass it as an array to the repository method findAll(),
             * do the same for $filters
             */
            $orderBy = $request->input('orderBy');
            $filters = $request->input('filters');
            $orderBy ? $orderBy = explode(',', $orderBy) : $orderBy = [];
            $filters ? $filters = explode(',', $filters) : $filters = [];

            return response()->json(
                $this->repo->search($q, $fields, $orderBy, $filters)
            );
        }
        // Catch any query exceptions and return a JSON formatted response (other exceptions will return text/html)
        catch(QueryException $exception) {
            return response()->json(['error'=>'Database error', 'errorInfo'=>$exception->errorInfo], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Validate the request and pass it's data to the repository create() method, treating any QueryException
     * in the process.
     * @param Request $request
     * @return string (json)
     */
    public function store(Request $request) {
        $json_body = json_decode($request->getContent(), true);

        // Validate the request before trying to store data in the database
        $validator_response = $this->validateInsert($json_body);

        // On validation failure, respond with status '302 Bad Request'
        if(!empty($validator_response['errors'])) {
            return response()->json(['errors' => $validator_response['errors']], Response::HTTP_BAD_REQUEST);
        }
        else {
            // Try to contact the repository and execute request
            try {
                return response()->json(
                    $this->repo->create($json_body)
                );
            }
            // Catch any query exceptions and return a JSON formatted response (other exceptions will return text/html)
            catch(QueryException $exception) {
                return response()->json(['error'=>'Database error', 'errorInfo'=>$exception->errorInfo], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
    }

    /**
     * Validate the request and pass it's data (partials allowed) to the repository update() method, treating any
     * QueryExceptions in the process.
     * @param string $id
     * @param Request $request
     * @return string (json)
     */
    public function update($id, Request $request) {
        $json_body = json_decode($request->getContent(), true);

        // Validate the request before trying to store data in the database
        $validator_response = $this->validateUpdate($json_body);

        // On validation failure, respond with status '302 Bad Request'
        if(!empty($validator_response['errors'])) {
            return response()->json(['errors' => $validator_response['errors']], Response::HTTP_BAD_REQUEST);
        }
        else {
            // Try to contact the repository and execute request
            try {
                return response()->json(
                    ['result' => $this->repo->update($id, $json_body)]
                );
            }
            // Catch any query exceptions and return a JSON formatted response (other exceptions will return text/html)
            catch(QueryException $exception) {
                return response()->json(['error'=>'Database error', 'errorInfo'=>$exception->errorInfo], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
    }

    /**
     * Call the repository delete() method, treating any QueryExceptions in the process.
     * @param string $id
     * @return string (json)
     */
    public function destroy($id) {
        // Try to contact the repository and execute request
        try {
            return response()->json(
                ['outcome' => $this->repo->delete([$id])]
            );
        }
        // Catch any query exceptions and return a JSON formatted response (other exceptions will return text/html)
        catch(QueryException $exception) {
            return response()->json(['error'=>'Database error', 'errorInfo'=>$exception->errorInfo], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Call the repository restore() method, treating any QueryExceptions in the process.
     * @param string $id
     * @return string (json)
     */
    public function restore($id) {
        // Try to contact the repository and execute request
        try {
            return response()->json(
                ['outcome' => $this->repo->restore([$id])]
            );
        }
        // Catch any query exceptions and return a JSON formatted response (other exceptions will return text/html)
        catch(QueryException $exception) {
            return response()->json(['error'=>'Database error', 'errorInfo'=>$exception->errorInfo], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
