<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\Status;
use App\Models\Problem;
use App\Models\Store;
use App\Models\Cash;
use App\Models\TicketCategory;
use App\Services\TicketService;
use App\Http\Requests\StoreTicketRequest;
use App\Http\Requests\UpdateTicketRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Inertia\Inertia;

class TicketController extends Controller
{
    use AuthorizesRequests;

    /**
     * @var TicketService
     */
    protected TicketService $ticketService;

    /**
     * @param TicketService $ticketService
     */
    public function __construct(TicketService $ticketService)
    {
        $this->ticketService = $ticketService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Проверяем право просмотра списка тикетов
        $this->authorize('viewAny', Ticket::class);

        $user = Auth::user();

        // Получаем тикеты через сервис
        $tickets = $this->ticketService->getTickets($user, [
            'search' => $request->search,
            'status' => $request->status,
            'problem' => $request->problem,
            'category' => $request->category,
        ]);

        return Inertia::render('Tickets/Index', [
            'tickets' => $tickets,
            'filters' => [
                'search' => $request->search,
                'status' => $request->status,
                'problem' => $request->problem,
                'category' => $request->category,
            ],
            'statuses' => Status::all(),
            'problems' => Problem::all(),
            'categories' => TicketCategory::all(),
            'userCategory' => $user->ticket_category_id,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Проверяем право создания тикетов
        $this->authorize('create', Ticket::class);

        return Inertia::render('Tickets/Create', [
            'statuses' => Status::all(),
            'problems' => Problem::all(),
            'stores' => Store::all(),
            'cashes' => Cash::all(),
            'categories' => TicketCategory::all(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTicketRequest $request)
    {
        $user = Auth::user();

        // Создаем тикет через сервис
        $ticket = $this->ticketService->createTicket($user, $request->validated());

        return redirect()->route('tickets.show', $ticket->id)
            ->with('success', 'Тикет успешно создан');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        // Получаем тикет через сервис
        $ticket = $this->ticketService->getTicket($id);

        // Проверяем право просмотра тикета
        $this->authorize('view', $ticket);

        return Inertia::render('Tickets/Show', [
            'ticket' => $ticket,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        // Получаем тикет через сервис
        $ticket = $this->ticketService->getTicket($id);

        // Проверяем право редактирования тикета (включая проверку статуса в Policy)
        $this->authorize('update', $ticket);

        return Inertia::render('Tickets/Edit', [
            'ticket' => $ticket,
            'statuses' => Status::all(),
            'problems' => Problem::all(),
            'stores' => Store::all(),
            'cashes' => Cash::all(),
            'categories' => TicketCategory::all(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTicketRequest $request, string $id)
    {
        $ticket = $this->ticketService->getTicket($id);

        // Проверяем право редактирования тикета (включая проверку статуса в Policy)
        $this->authorize('update', $ticket);

        // Обновляем тикет через сервис
        $this->ticketService->updateTicket($ticket, $request->validated());

        return redirect()->route('tickets.show', $ticket->id)
            ->with('success', 'Тикет успешно обновлен');
    }

    /**
     * Update the status of the specified resource.
     */
    public function updateStatus(Request $request, string $id)
    {
        $ticket = $this->ticketService->getTicket($id);

        // Проверяем право изменения статуса
        $this->authorize('updateStatus', $ticket);

        $validated = $request->validate([
            'status_id' => 'required|exists:statuses,id',
        ]);

        // Обновляем статус через сервис
        $this->ticketService->updateStatus($ticket, $validated['status_id']);

        return back()->with('success', 'Статус тикета обновлен');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $ticket = $this->ticketService->getTicket($id);

        // Проверяем право удаления тикета
        $this->authorize('delete', $ticket);

        // Удаляем тикет через сервис
        $this->ticketService->deleteTicket($ticket);

        return redirect()->route('tickets.index')
            ->with('success', 'Тикет успешно удален');
    }
}
