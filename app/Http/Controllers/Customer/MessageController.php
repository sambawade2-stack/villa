<?php

declare(strict_types=1);

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Property;
use App\Services\Messaging\MessagingService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function __construct(private readonly MessagingService $messaging) {}

    public function index(Request $request): View
    {
        return view('customer.messages.index', [
            'conversations' => $request->user()->conversations()
                ->with(['property:id,name,slug', 'latestMessage'])
                ->recent()
                ->paginate(15),
        ]);
    }

    public function show(Request $request, Conversation $conversation): View
    {
        $this->authorize('view', $conversation);

        $this->messaging->markReadForCustomer($conversation);

        return view('customer.messages.show', [
            'conversation' => $conversation->load(['messages.sender:id,first_name,last_name,role', 'property:id,name,slug']),
        ]);
    }

    public function store(Request $request, Conversation $conversation): RedirectResponse
    {
        $this->authorize('view', $conversation);

        $data = $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:4000'],
        ]);

        $this->messaging->post($conversation, $request->user(), $data['body']);

        return back();
    }

    /** Ouvre un fil depuis une fiche villa ou depuis l'espace client. */
    public function create(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'property' => ['nullable', 'string', 'exists:properties,slug'],
            'subject' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'min:10', 'max:4000'],
        ]);

        $property = isset($data['property'])
            ? Property::where('slug', $data['property'])->first()
            : null;

        $conversation = $this->messaging->openConversation($request->user(), $data['subject'], $property);
        $this->messaging->post($conversation, $request->user(), $data['body']);

        return redirect()->route('messages.show', $conversation)
            ->with('status', __('Message envoyé. Notre équipe répond sous 2 heures ouvrées.'));
    }
}
