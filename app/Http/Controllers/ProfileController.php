<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateUserProfile;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;

class ProfileController extends Controller
{
    use AuthorizesRequests;

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserProfile $request, User $user)
    {
        $this->authorize('update', [User::class, $user]);

        $user->update($request->validated());
        $user->refresh();

        return ResponseBuilder::asSuccess()
            ->withMessage('Profile updated successfully')
            ->withData($user)
            ->build();

    }

    public function uploadProfilePicture()
    {

    }
}
