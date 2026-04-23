<?php

namespace App\Http\Controllers;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends BaseController
{
    public function index(Request $request)
    {
        $allowedSortFields = ['id', 'name', 'guard_name', 'created_at', 'updated_at'];
        $allowedFilterFields = ['id', 'name', 'guard_name'];
        $allowedOperators = ['=', '!=', 'like'];

        $sortBy = 'id';
        $sortDir = 'asc';
        if (!empty($request['sort'][0]['field']) && in_array($request['sort'][0]['field'], $allowedSortFields, true)) {
            $sortBy = $request['sort'][0]['field'];
        }
        if (!empty($request['sort'][0]['dir']) && in_array(strtolower($request['sort'][0]['dir']), ['asc', 'desc'], true)) {
            $sortDir = strtolower($request['sort'][0]['dir']);
        }

        $filters = $request['filter'] ?? [];
        $query = Role::with('permissions')->orderBy($sortBy, $sortDir);

        if ($filters) {
            foreach ($filters as $filter) {
                $field = $filter['field'] ?? null;
                $operator = $filter['type'] ?? '=';
                $searchTerm = $filter['value'] ?? null;

                if (!$field || !in_array($field, $allowedFilterFields, true) || !in_array($operator, $allowedOperators, true) || $searchTerm === null) {
                    continue;
                }

                if ($operator == 'like') {
                    $searchTerm = '%' . $searchTerm . '%';
                }

                $query->where($field, $operator, $searchTerm);
            }
        }

        $item = $query->get();
        $data = [
            "data" => $item->toArray(),
            'permissions' => Permission::all(),
        ];

        return response()->json($data);
    }
    // public function index(){

    //     $roles = Role::with('permissions')->get();
    //     // $permissions = Permission::all();
    //     $data = [
    //         "roles" => $roles, 
    //         "permissions" => $permissions
    //     ];
    //     return $this->sendResponse($data, '');
    // }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required',
            'permissions' => 'array',
            'permissions.*' => 'uuid|exists:permissions,id',
        ]);

        $role = Role::create([
            "name" => $validated['name'],
            "guard_name" => "web",
        ]);

        $role->syncPermissions($validated['permissions'] ?? []);

        return $this->sendResponse($role, '');
    }

    public function update(Request $request, $id)
    {
        $role = Role::find($id);

        if (!$role) {
            return $this->sendError('Role not found', [], 404);
        }

        $validated = $request->validate([
            'name' => 'required',
            'permissions' => 'array',
            'permissions.*' => 'uuid|exists:permissions,id',
        ]);

        $role->name = $validated['name'];
        $role->guard_name = 'web';
        $role->save();

        $role->syncPermissions($validated['permissions'] ?? []);

        return $this->sendResponse($role, '');
    }

    public function destroy($id)
    {
        $role = Role::find($id);

        if (!$role) {
            return $this->sendError('Role not found', [], 404);
        }

        $role->delete();

        return $this->sendResponse('', 'Role Deleted');
    }
}
