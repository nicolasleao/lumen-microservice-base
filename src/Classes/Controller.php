<?php

namespace Nicolasleao\BaseService\Classes;

use Illuminate\Http\Request;
use Laravel\Lumen\Routing\Controller as BaseController;

class Controller extends BaseController
{
    /**
     * Service to interact with the Product Model.
     * @var ProductService
     */
    protected Service $service;

    /**
     * Create a new controller instance and inject the $service dependency.
     * @return void
     */
    public function __construct(Service $service) {
        $this->service = $service;
    }

    public function showAll(Request $request) {
        return $this->service->showAll($request);
    }

    public function show($id) {
        return $this->service->show($id);
    }

    public function search(string $q, Request $request) {
        return $this->service->search($q, $request);
    }

    public function store(Request $request) {
        return $this->service->store($request);
    }

    public function update($id, Request $request) {
        return $this->service->update($id, $request);
    }

    public function destroy($id) {
        return $this->service->destroy($id);
    }

    public function restore($id) {
        return $this->service->restore($id);
    }
}
