<?php

namespace App\Actions;

use App\Enums\MediaTypeEnum;
use App\Models\User;
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
            $user->addMedia($file)->toMediaCollection(MediaTypeEnum::ProfilePicture);
        }

        return $user;
    }
}
