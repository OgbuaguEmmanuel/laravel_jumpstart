<?php

namespace App\Actions;

use App\Enums\MediaTypeEnum;
use App\Models\User;
use App\Notifications\WelcomeUserNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;

class CreateUserAction
{
    /**
     * Create a new class instance.
     */
    public function handle(array $data, ?UploadedFile $file = null): User
    {
        $user =  User::create([
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone_number' => $data['phone_number'] ?? null,
            'password' => Hash::make($data['password']),
        ]);

        if (!empty($data['profile_picture'])) {
            $media = $user->addMedia($file)->toMediaCollection(MediaTypeEnum::ProfilePicture);

            activity()
                ->causedBy($user)
                ->performedOn($media)
                ->withProperties([
                    'media_id' => $media->id,
                    'file_name' => $media->file_name,
                    'collection_name' => $media->collection_name,
                    'mime_type' => $media->mime_type,
                ])
                ->log('Profile picture uploaded');
        }

        $user->notify(new WelcomeUserNotification(config('frontend.dashboard')));
        
        return $user;
    }
}
