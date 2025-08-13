<?php

namespace App\Http\Controllers\V1;

use App\Enums\PermissionTypeEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateSupportTicketRequest;
use App\Http\Requests\UpdateSupportTicketRequest;
use App\Models\SupportTicket;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class SupportTicketController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (Auth::user()->hasPermissionTo(PermissionTypeEnum::viewSupportTicket)) {
            $tickets = QueryBuilder::for(SupportTicket::query())
                ->allowedIncludes(['user'])
                ->defaultSort('-created_at')
                ->allowedSorts(['created_at', 'priority', 'status'])
                ->allowedFilters([
                    'subject',
                    'status',
                    'priority',
                    AllowedFilter::exact('user_id'),
                ])
                ->paginate(15)
                ->appends(request()->query());

        } else {
            $tickets = QueryBuilder::for(Auth::user()->supportTickets()->latest())
                ->allowedIncludes(['user'])
                ->allowedFilters([
                    'subject',
                    'status',
                    'priority',
                ])
                ->paginate(15)
                ->appends(request()->query());
        }

        return ResponseBuilder::asSuccess()
            ->withData($tickets)
            ->withMessage('Tickets fetched')
            ->build();
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateSupportTicketRequest $request)
    {
        $ticket = Auth::user()->supportTickets()->create($request->validated());

        return ResponseBuilder::asSuccess()
            ->withData($ticket)
            ->withHttpCode(201)
            ->withMessage('Ticket raised')
            ->build();
    }

    /**
     * Display the specified resource.
     */
    public function show(SupportTicket $supportTicket)
    {
        $this->authorize('view', $supportTicket);

        return ResponseBuilder::asSuccess()
            ->withMessage('Ticket fetched')
            ->withData($supportTicket->load('messages.user'))
            ->build();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSupportTicketRequest $request, SupportTicket $supportTicket)
    {
        $this->authorize('update', SupportTicket::class);

        $supportTicket->fill($request->validated());
        $supportTicket->updated_by = Auth::user()->id;
        $supportTicket->save();

        return ResponseBuilder::asSuccess()
            ->withData($supportTicket)
            ->withMessage('Ticket updated')
            ->build();
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SupportTicket $ticket)
    {
        $this->authorize('delete', $ticket);

        $ticket->delete();

        return ResponseBuilder::asSuccess()
            ->withMessage('Ticket deleted successfully')
            ->build();
    }
}
