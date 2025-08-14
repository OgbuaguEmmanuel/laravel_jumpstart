<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateSupportMessageRequest;
use App\Models\SupportTicket;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;

class SupportMessageController extends Controller
{
    use AuthorizesRequests;

    public function store(CreateSupportMessageRequest $request, SupportTicket $supportTicket)
    {
        $this->authorize('view', $supportTicket);

        $message = $supportTicket->messages()->create([
            'user_id' => Auth::id(),
            'message' => $request->validated('message'),
        ]);

        return ResponseBuilder::asSuccess()
            ->withData($message)
            ->withHttpCode(201)
            ->withMessage('Message sent')
            ->build();
    }
}
