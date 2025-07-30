<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Spatie\QueryBuilder\QueryBuilder;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $users = QueryBuilder::for(User::query())
            ->defaultSort('-created_at')
            ->allowedFilters('first_name','last_name','email')
            ->allowedSorts(['first_name','last_name'])
            ->paginate($request->get('per_page'));

        return ResponseBuilder::asSuccess()
            ->withData(['users' => $users])
            ->withMessage('Users successfully fetched')
            ->build();
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        return ResponseBuilder::asSuccess()
            ->withData(['user' => $user])
            ->withMessage('User fetched')
            ->build();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {

    }

    public function toggleUserStatus(User $user)
    {

    }
}
