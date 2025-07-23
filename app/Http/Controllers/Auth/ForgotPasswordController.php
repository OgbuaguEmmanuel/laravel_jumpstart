<?php

namespace App\Http\Controllers\Auth;

use App\Auth\ForgotPasswordAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Traits\AuthHelpers;
use Illuminate\Http\Request;

class ForgotPasswordController extends Controller
{
    use AuthHelpers;

    public function forgotPassword(ForgotPasswordRequest $request, ForgotPasswordAction $action)
    {


        try {
            DB::beginTransaction();

            $data = $request->validated();

            if ($request->filled('email')){
                $user = User::where('email', $data['email'])->first();
            } else {
                $user = User::where('phone', $data['phone'])->first();
            }

            $user->reset_token = Random::generate(6, '0-9');
            $user->token_time = now()->addMinutes(60);
            $user->save();

            if ($request->type == 'email' || $request->type == 'combined') {
                //Send verification email here
                Mail::to($user->email)->send(new ForgotPasswordOPTEmail($user));

                DB::commit();
                $maskedEmail = $this::maskEmailAddress($user->email);

                return ResponseBuilder::asSuccess()
                    ->withMessage("Password reset OPT have been sent to $maskedEmail")
                    ->build();
            }

            if ($request->type == 'phone' || $request->type == 'combined') {
                logger()->info('Sending SMS to: ' . $user->phone);

                $message = $user->reset_token;
                $type = SMSType::PASSWORD_RESET();
                $recipientName = $user->first_name . ' ' . $user->last_name;
                $senderName = $user->organization->name;
                $senderEmail = $user->organization->email;

                $this->dispatchSmsJob(
                    $senderName, $user->organization_id, $senderEmail,
                    $recipientName, $user->phone, $message, $type
                );

                DB::commit();
                $maskedPhone = $this::maskPhoneNumber($user->phone);
                return ResponseBuilder::asSuccess()
                    ->withMessage("Password reset OPT have been sent to $maskedPhone")
                    ->build();
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return $this::exception($e);
        }
    }
}
