<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\ConversationStatus;
use App\Enums\MessageChannel;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Services\Messaging\MessagingService;
use App\Services\WhatsApp\WhatsAppManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MessageController extends Controller
{
    public function __construct(
        private readonly MessagingService $messaging,
        private readonly WhatsAppManager $whatsapp,
    ) {}

    public function index(Request $request): View
    {
        $filter = $request->query('filter');

        return view('admin.messages.index', [
            'conversations' => Conversation::query()
                ->with(['user:id,first_name,last_name,email', 'property:id,name,slug', 'latestMessage'])
                ->when($filter === 'non-lus', fn ($q) => $q->where('admin_unread_count', '>', 0))
                ->when($filter === 'whatsapp', fn ($q) => $q->whereNotNull('whatsapp_number'))
                ->when($filter === 'fermees', fn ($q) => $q->where('status', ConversationStatus::Closed))
                ->when($filter === null, fn ($q) => $q->open())
                ->recent()
                ->paginate(20)
                ->withQueryString(),
            'filter' => $filter,
            'unread' => Conversation::query()->where('admin_unread_count', '>', 0)->count(),
        ]);
    }

    public function show(Conversation $conversation): View
    {
        $this->messaging->markReadForAdmin($conversation);

        return view('admin.messages.show', [
            'conversation' => $conversation->load([
                'messages.sender:id,first_name,last_name,role',
                'user', 'property:id,name,slug', 'booking:id,reference',
            ]),
            'whatsappReady' => $this->whatsapp->sendsForReal(),
        ]);
    }

    public function reply(Request $request, Conversation $conversation): RedirectResponse
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'min:2', 'max:4000'],
            'channel' => ['required', Rule::enum(MessageChannel::class)],
        ]);

        $channel = MessageChannel::from($data['channel']);

        if ($channel === MessageChannel::WhatsApp && blank($conversation->whatsapp_number)) {
            return back()->with('error', __('Ce client n\'a pas de numéro WhatsApp enregistré.'));
        }

        $message = $this->messaging->replyAsAdmin($conversation, $request->user(), $data['body'], $channel);

        if ($message->hasFailed()) {
            return back()->with('error', __('Message enregistré, mais l\'envoi WhatsApp a échoué : :raison', [
                'raison' => $message->failure_reason,
            ]));
        }

        return back();
    }

    public function toggleStatus(Conversation $conversation): RedirectResponse
    {
        $conversation->update([
            'status' => $conversation->status === ConversationStatus::Open
                ? ConversationStatus::Closed
                : ConversationStatus::Open,
        ]);

        return back()->with('status', __('Conversation mise à jour.'));
    }
}
