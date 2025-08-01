<?php

namespace App\Http\Controllers;

use App\Enums\MediaTypeEnum;
use App\Http\Requests\ProfileUploadRequest;
use App\Http\Requests\UpdateUserProfile;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
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

    public function uploadProfilePicture(ProfileUploadRequest $request, User $user)
    {
        $this->authorize('uploadProfileImage', [User::class, $user]);

        $user->addMedia($request->file('image'))
            ->toMediaCollection(MediaTypeEnum::ProfilePicture);

        $user->profilePicture = $user->profilePicture();
        
        return ResponseBuilder::asSuccess()
            ->withData($user)
            ->withMessage('Profile uploaded successfully')
            ->build();
    }
}
