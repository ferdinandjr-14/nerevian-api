<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Concerns\AuthorizesApiRequests;
use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Rol;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminLookupController extends Controller
{
    use AuthorizesApiRequests;

    public function roles(Request $request): JsonResponse
    {
        $this->requireRoles($request, ['admin']);

        return response()->json([
            'roles' => Rol::query()->orderBy('id')->get(),
        ]);
    }

    public function clients(Request $request): JsonResponse
    {
        $this->requireRoles($request, ['admin']);

        return response()->json([
            'clients' => Client::query()->orderBy('nom')->get(),
        ]);
    }
}
