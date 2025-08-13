<?php

namespace App\Http\Controllers\V1;

use App\Enums\MediaTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileUploadRequest;
use App\Http\Requests\UpdateUserProfile;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;

class ProfileController extends Controller
{
    use AuthorizesRequests;

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserProfile $request, User $user)
    {
        $this->authorize('update', $user);

        $user->update($request->validated());
        $user->refresh();

        return ResponseBuilder::asSuccess()
            ->withMessage('Profile updated successfully')
            ->withData($user)
            ->build();

    }

    public function uploadProfilePicture(ProfileUploadRequest $request, User $user)
    {
        $this->authorize('uploadProfileImage', $user);

        $media = $user->addMedia($request->file('profile_image'))
            ->toMediaCollection(MediaTypeEnum::ProfilePicture);

        $user->profilePicture = $user->profilePicture();

        activity()
            ->causedBy(Auth::user())
            ->performedOn($media)
            ->withProperties([
                'media_id' => $media->id,
                'file_name' => $media->file_name,
                'collection_name' => $media->collection_name,
                'mime_type' => $media->mime_type,
            ])
            ->log('Profile picture uploaded');

        return ResponseBuilder::asSuccess()
            ->withData($user)
            ->withMessage('Profile uploaded successfully')
            ->build();
    }
}
