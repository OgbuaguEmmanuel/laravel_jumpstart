<?php

namespace App\Http\Controllers\V1;

use App\Enums\PermissionTypeEnum;
use App\Facades\Settings;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateSupportTicketRequest;
use App\Http\Requests\UpdateSupportTicketRequest;
use App\Models\SupportTicket;
use App\Sorts\CustomSort;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use MarcinOrlowski\ResponseBuilder\ResponseBuilder;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

class SupportTicketController extends Controller
{
    use AuthorizesRequests;

    protected int $per_page;

    public function __construct()
    {
        $this->per_page = Settings::get('pagination_size');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        if (Auth::user()->hasPermissionTo(PermissionTypeEnum::viewSupportTicket)) {
            $query = QueryBuilder::for(SupportTicket::query());
        } else {
            $query = QueryBuilder::for(Auth::user()->supportTickets()->latest());
        }

        $tickets = $query
            ->allowedFields([
                'support_tickets.id',
                'support_tickets.subject',
                'support_tickets.status',
                'support_tickets.priority',
                'support_tickets.description',
                'support_tickets.user_id',
                'support_tickets.updated_by',
                'support_tickets.created_at',
                'user.id',
                'user.first_name',
                'user.last_name',
                'user.email',
                'user.is_active',
                'user.is_locked',
                'user.created_at',
            ])
            ->allowedIncludes([
                'user','updatedBy','messages'
            ])
            ->defaultSort('-created_at')
            ->allowedSorts([
                'created_at',
                AllowedSort::custom('priority', new CustomSort),
                AllowedSort::custom('status', new CustomSort),
            ])
            ->allowedFilters([
                'subject',
                'status',
                'priority',
                AllowedFilter::exact('user_id'),
                AllowedFilter::exact('updated_by'),

            ])
            ->paginate(request()->get('per_page') ?? $this->per_page)
            ->appends(request()->query());

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
    public function show(SupportTicket $ticket)
    {
        $this->authorize('view', $ticket);

        return ResponseBuilder::asSuccess()
            ->withMessage('Ticket fetched')
            ->withData($ticket->load('messages.user'))
            ->build();
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSupportTicketRequest $request, SupportTicket $ticket)
    {
        $this->authorize('update', SupportTicket::class);

        $ticket->fill($request->validated());
        $ticket->updated_by = Auth::user()->id;
        $ticket->save();

        return ResponseBuilder::asSuccess()
            ->withData($ticket)
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
