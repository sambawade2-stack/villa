<?php

declare(strict_types=1);

use App\Enums\MessageChannel;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Property;
use App\Models\Setting;
use App\Models\User;
use App\Services\Messaging\MessagingService;
use App\Services\WhatsApp\DTO\InboundMessage;
use App\Services\WhatsApp\WhatsAppLink;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->customer = User::factory()->create(['whatsapp' => '+221 77 123 45 67']);
    $this->messaging = app(MessagingService::class);
});

/*
|--------------------------------------------------------------------------
| Fil client ↔ admin
|--------------------------------------------------------------------------
*/

it('ouvre un fil depuis une fiche villa', function () {
    $property = Property::factory()->published()->create();

    $this->actingAs($this->customer)->post(route('messages.create'), [
        'property' => $property->slug,
        'subject' => 'Disponibilités en décembre',
        'body' => 'Bonjour, la villa est-elle libre la semaine de Noël ?',
    ])->assertRedirect();

    $conversation = Conversation::first();

    expect($conversation->user_id)->toBe($this->customer->id)
        ->and($conversation->property_id)->toBe($property->id)
        ->and($conversation->messages)->toHaveCount(1)
        // Le message part vers l'administration : c'est son compteur qui monte.
        ->and($conversation->admin_unread_count)->toBe(1)
        ->and($conversation->customer_unread_count)->toBe(0);
});

it('reprend le fil ouvert plutôt que d\'en créer un second', function () {
    $property = Property::factory()->published()->create();

    $first = $this->messaging->openConversation($this->customer, 'Question', $property);
    $second = $this->messaging->openConversation($this->customer, 'Autre question', $property);

    expect($second->id)->toBe($first->id)
        ->and(Conversation::count())->toBe(1);
});

it('interdit à un client de lire le fil d\'un autre', function () {
    $conversation = $this->messaging->openConversation(User::factory()->create(), 'Privé');

    $this->actingAs($this->customer)
        ->get(route('messages.show', $conversation))
        ->assertNotFound();
});

it('interdit à un client d\'écrire dans le fil d\'un autre', function () {
    $conversation = $this->messaging->openConversation(User::factory()->create(), 'Privé');

    $this->actingAs($this->customer)
        ->post(route('messages.store', $conversation), ['body' => 'Intrusion'])
        ->assertNotFound();

    expect(Message::count())->toBe(0);
});

it('remet les non-lus à zéro à l\'ouverture du fil', function () {
    $conversation = $this->messaging->openConversation($this->customer, 'Sujet');
    $this->messaging->replyAsAdmin($conversation, $this->admin, 'Bonjour, voici la réponse.');

    expect($conversation->fresh()->customer_unread_count)->toBe(1);

    $this->actingAs($this->customer)->get(route('messages.show', $conversation))->assertOk();

    expect($conversation->fresh()->customer_unread_count)->toBe(0);
});

it('permet à l\'administrateur de répondre dans le fil', function () {
    $conversation = $this->messaging->openConversation($this->customer, 'Sujet');
    $this->messaging->post($conversation, $this->customer, 'Ma question porte sur les dates.');

    $this->actingAs($this->admin)->post(route('admin.messages.reply', $conversation), [
        'body' => 'Bonjour, ces dates sont libres.',
        'channel' => MessageChannel::InApp->value,
    ])->assertRedirect();

    expect($conversation->fresh()->customer_unread_count)->toBe(1)
        ->and($conversation->messages()->count())->toBe(2);
});

it('ferme la messagerie de l\'administration à un client', function () {
    $conversation = $this->messaging->openConversation($this->customer, 'Sujet');

    $this->actingAs($this->customer)->get(route('admin.messages.index'))->assertNotFound();
    $this->actingAs($this->customer)->get(route('admin.messages.show', $conversation))->assertNotFound();
});

/*
|--------------------------------------------------------------------------
| WhatsApp — lien direct
|--------------------------------------------------------------------------
*/

it('construit un lien wa.me avec le message pré-rédigé', function () {
    Setting::put('contact.whatsapp', '+221 77 000 00 00');
    Setting::flushCache();

    $url = WhatsAppLink::to('Bonjour à tous');

    expect($url)->toStartWith('https://wa.me/221770000000')
        ->and($url)->toContain(rawurlencode('Bonjour à tous'));
});

it('n\'affiche aucun bouton WhatsApp sans numéro configuré', function () {
    Setting::put('contact.whatsapp', null);
    Setting::flushCache();
    config()->set('whatsapp.number', '');

    expect(WhatsAppLink::to('Test'))->toBeNull()
        ->and(WhatsAppLink::isAvailable())->toBeFalse();
});

/*
|--------------------------------------------------------------------------
| WhatsApp — passerelle et webhook
|--------------------------------------------------------------------------
*/

it('journalise sans prétendre envoyer quand la passerelle n\'est pas configurée', function () {
    Log::shouldReceive('channel')->andReturnSelf();
    Log::shouldReceive('info')->once();

    $conversation = $this->messaging->openConversation($this->customer, 'Sujet');
    $message = $this->messaging->replyAsAdmin($conversation, $this->admin, 'Bonjour', MessageChannel::WhatsApp);

    // Le message existe dans le fil et porte une référence de journal, pas un
    // identifiant Meta : rien n'est présenté comme réellement envoyé.
    expect($message->delivered_at)->not->toBeNull()
        ->and($message->external_id)->toStartWith('log_');
});

it('marque le message en échec si le client n\'a pas de numéro', function () {
    $sansNumero = User::factory()->create(['whatsapp' => null, 'phone' => null]);
    $conversation = $this->messaging->openConversation($sansNumero, 'Sujet');

    $message = $this->messaging->replyAsAdmin($conversation, $this->admin, 'Bonjour', MessageChannel::WhatsApp);

    expect($message->hasFailed())->toBeTrue()
        ->and($message->failure_reason)->toContain('numéro');
});

it('rattache un message WhatsApp entrant au client dont le numéro correspond', function () {
    $message = $this->messaging->ingestWhatsApp(new InboundMessage(
        from: '221771234567',
        body: 'Bonjour, je cherche une villa à Saly',
        externalId: 'wamid.TEST1',
        profileName: 'Moussa',
    ));

    expect($message)->not->toBeNull()
        ->and($message->channel)->toBe(MessageChannel::WhatsApp)
        ->and($message->conversation->user_id)->toBe($this->customer->id)
        ->and($message->conversation->whatsapp_number)->toBe('221771234567');
});

it('ouvre un fil sans compte quand le numéro est inconnu', function () {
    $message = $this->messaging->ingestWhatsApp(new InboundMessage(
        from: '221769998877',
        body: 'Bonsoir',
        externalId: 'wamid.TEST2',
        profileName: 'Inconnu',
    ));

    expect($message->conversation->user_id)->toBeNull()
        ->and($message->conversation->whatsapp_number)->toBe('221769998877');
});

it('ignore un message déjà reçu, quel que soit le nombre de renvois', function () {
    $inbound = new InboundMessage(from: '221771234567', body: 'Bonjour', externalId: 'wamid.SAME');

    $first = $this->messaging->ingestWhatsApp($inbound);
    $second = $this->messaging->ingestWhatsApp($inbound);
    $third = $this->messaging->ingestWhatsApp($inbound);

    expect($first)->not->toBeNull()
        ->and($second)->toBeNull()
        ->and($third)->toBeNull()
        ->and(Message::count())->toBe(1);
});

it('ouvre la fenêtre de 24 heures à la réception d\'un message', function () {
    $message = $this->messaging->ingestWhatsApp(new InboundMessage(
        from: '221771234567', body: 'Bonjour', externalId: 'wamid.WINDOW',
    ));

    expect($message->conversation->fresh()->whatsappWindowIsOpen())->toBeTrue();

    $message->conversation->forceFill(['last_inbound_at' => now()->subHours(25)])->saveQuietly();

    expect($message->conversation->fresh()->whatsappWindowIsOpen())->toBeFalse();
});

it('rejette une notification de webhook non signée', function () {
    $this->postJson(route('webhooks.whatsapp.handle'), ['entry' => []])
        ->assertForbidden();

    expect(Message::count())->toBe(0);
});

it('refuse la vérification d\'URL avec un mauvais jeton', function () {
    config()->set('whatsapp.cloud.verify_token', 'le-bon-jeton');

    $this->get(route('webhooks.whatsapp.verify', [
        'hub_mode' => 'subscribe',
        'hub_verify_token' => 'le-mauvais',
        'hub_challenge' => '12345',
    ]))->assertForbidden();
});

it('renvoie le défi quand le jeton de vérification correspond', function () {
    config()->set('whatsapp.cloud.verify_token', 'le-bon-jeton');

    $this->get(route('webhooks.whatsapp.verify', [
        'hub_mode' => 'subscribe',
        'hub_verify_token' => 'le-bon-jeton',
        'hub_challenge' => '12345',
    ]))->assertOk()->assertSee('12345');
});
