<?php

declare(strict_types=1);

use App\Enums\ComplianceItem;
use App\Enums\ComplianceStatus;
use App\Models\ComplianceCheck;
use App\Models\Property;
use App\Models\PropertyImage;
use App\Models\User;
use App\Services\Compliance\ComplianceService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->customer = User::factory()->create();
    $this->property = Property::factory()->published()->create();
    $this->compliance = app(ComplianceService::class);
    $this->compliance->ensureChecklist($this->property);
});

/*
|--------------------------------------------------------------------------
| Étanchéité — qui peut voir quoi
|--------------------------------------------------------------------------
*/

it('renvoie un visiteur vers la connexion', function () {
    // `auth` s'exécute avant `admin` : un visiteur est invité à se connecter.
    $this->get(route('admin.villas.compliance.show', $this->property))
        ->assertRedirect(route('login'));
});

it('ferme le dossier à un client authentifié', function () {
    // 404 et non 403 : rien ne justifie de confirmer l'existence de l'écran.
    $this->actingAs($this->customer)
        ->get(route('admin.villas.compliance.show', $this->property))
        ->assertNotFound();
});

it('ferme tout le préfixe /admin à un client', function (string $path) {
    $this->actingAs($this->customer)->get($path)->assertNotFound();
})->with([
    'tableau de bord' => '/admin',
    'liste des villas' => '/admin/villas',
]);

it('ouvre le dossier à un administrateur', function () {
    $this->actingAs($this->admin)
        ->get(route('admin.villas.compliance.show', $this->property))
        ->assertOk()
        ->assertSee('Documents de conformité')
        ->assertSee('Identité du propriétaire');
});

/*
|--------------------------------------------------------------------------
| Documents — stockage et diffusion
|--------------------------------------------------------------------------
*/

it('stocke la pièce sur le disque privé, jamais dans le dossier public', function () {
    Storage::fake('compliance');
    Storage::fake('public');

    $check = $this->property->complianceChecks()->where('item', ComplianceItem::OwnerIdentity)->first();

    $this->actingAs($this->admin)->post(
        route('admin.villas.compliance.upload', [$this->property, $check]),
        ['document' => UploadedFile::fake()->create('carte-identite.pdf', 120, 'application/pdf')]
    )->assertRedirect();

    $check->refresh();

    expect($check->document_path)->not->toBeNull()
        ->and($check->document_name)->toBe('carte-identite.pdf')
        // Déposer n'est pas vérifier.
        ->and($check->status)->toBe(ComplianceStatus::Provided);

    Storage::disk('compliance')->assertExists($check->document_path);
    Storage::disk('public')->assertDirectoryEmpty('');
});

it('ne réutilise jamais le nom du fichier déposé comme nom de stockage', function () {
    Storage::fake('compliance');

    $check = $this->property->complianceChecks()->where('item', ComplianceItem::OwnershipProof)->first();

    $this->actingAs($this->admin)->post(
        route('admin.villas.compliance.upload', [$this->property, $check]),
        ['document' => UploadedFile::fake()->create('../../titre foncier.pdf', 50, 'application/pdf')]
    );

    // Le chemin stocké est un identifiant aléatoire : ni traversée, ni nom devinable.
    expect($check->fresh()->document_path)
        ->not->toContain('..')
        ->not->toContain('titre foncier')
        ->toMatch('#^\d+/[0-9a-f-]{36}\.pdf$#');
});

it('refuse un fichier exécutable', function () {
    Storage::fake('compliance');

    $check = $this->property->complianceChecks()->where('item', ComplianceItem::OwnerIdentity)->first();

    $this->actingAs($this->admin)->post(
        route('admin.villas.compliance.upload', [$this->property, $check]),
        ['document' => UploadedFile::fake()->create('charge.php', 10, 'application/x-php')]
    )->assertSessionHasErrors('document');

    expect($check->fresh()->document_path)->toBeNull();
});

it('ne laisse télécharger un document qu\'à un administrateur', function () {
    Storage::fake('compliance');

    $check = $this->property->complianceChecks()->where('item', ComplianceItem::OwnerIdentity)->first();
    $this->actingAs($this->admin)->post(
        route('admin.villas.compliance.upload', [$this->property, $check]),
        ['document' => UploadedFile::fake()->create('piece.pdf', 30, 'application/pdf')]
    );

    $url = route('admin.villas.compliance.download', [$this->property, $check]);

    $this->post(route('logout'));
    $this->get($url)->assertRedirect(route('login'));

    $this->actingAs($this->customer)->get($url)->assertNotFound();

    $this->actingAs($this->admin)->get($url)
        ->assertOk()
        ->assertHeader('Cache-Control', 'max-age=0, no-store, private')
        ->assertHeader('X-Robots-Tag', 'noindex, nofollow');
});

it('force le téléchargement du document au lieu de l\'afficher inline', function () {
    Storage::fake('compliance');

    $check = $this->property->complianceChecks()->where('item', ComplianceItem::OwnerIdentity)->first();
    $this->actingAs($this->admin)->post(
        route('admin.villas.compliance.upload', [$this->property, $check]),
        ['document' => UploadedFile::fake()->create('piece.pdf', 30, 'application/pdf')]
    );

    $response = $this->actingAs($this->admin)->get(
        route('admin.villas.compliance.download', [$this->property, $check])
    );

    // « inline » laisserait un PDF piégé s'ouvrir directement dans l'onglet
    // de l'administrateur ; « attachment » force une étape de téléchargement.
    expect($response->headers->get('Content-Disposition'))->toContain('attachment');
});

it('interdit de lire le dossier d\'une villa par l\'URL d\'une autre', function () {
    $other = Property::factory()->published()->create();
    $this->compliance->ensureChecklist($other);

    $foreignCheck = $other->complianceChecks()->first();

    // L'identifiant de la pièce appartient à `other`, l'URL désigne `property`.
    $this->actingAs($this->admin)
        ->get(route('admin.villas.compliance.download', [$this->property, $foreignCheck]))
        ->assertNotFound();
});

/*
|--------------------------------------------------------------------------
| Rien ne fuit côté public
|--------------------------------------------------------------------------
*/

it('n\'expose jamais le dossier sur la fiche publique', function () {
    Storage::fake('compliance');
    PropertyImage::factory()->primary()->create(['property_id' => $this->property->id]);

    $check = $this->property->complianceChecks()->where('item', ComplianceItem::OwnerIdentity)->first();
    $this->actingAs($this->admin)->post(
        route('admin.villas.compliance.upload', [$this->property, $check]),
        ['document' => UploadedFile::fake()->create('cni-mamadou-fall.pdf', 30, 'application/pdf')]
    );
    $check->refresh();
    $check->update(['notes' => 'Note interne confidentielle', 'reference' => 'RCCM-SN-0001']);

    $this->post(route('logout'));

    $this->get(route('villas.show', [$this->property->destination, $this->property]))
        ->assertOk()
        ->assertDontSee('cni-mamadou-fall.pdf')
        ->assertDontSee('Note interne confidentielle')
        ->assertDontSee('RCCM-SN-0001')
        ->assertDontSee($check->document_path)
        ->assertDontSee('conformite');
});

it('retire le chemin et les notes de toute sérialisation', function () {
    $check = $this->property->complianceChecks()->first();
    $check->update(['document_path' => '1/secret.pdf', 'notes' => 'interne']);

    expect($check->fresh()->toArray())
        ->not->toHaveKey('document_path')
        ->not->toHaveKey('notes');
});

/*
|--------------------------------------------------------------------------
| Le badge découle du dossier
|--------------------------------------------------------------------------
*/

it('n\'active le badge « vérifiée » que si toutes les pièces requises le sont', function () {
    expect($this->property->fresh()->is_verified)->toBeFalse();

    $this->property->complianceChecks()->update(['status' => ComplianceStatus::Verified]);
    $this->compliance->syncPropertyVerification($this->property);

    expect($this->property->fresh()->is_verified)->toBeTrue();
});

it('éteint le badge dès qu\'une pièce est refusée', function () {
    $this->property->complianceChecks()->update(['status' => ComplianceStatus::Verified]);
    $this->compliance->syncPropertyVerification($this->property);
    expect($this->property->fresh()->is_verified)->toBeTrue();

    $check = $this->property->complianceChecks()->where('item', ComplianceItem::RentalTerms)->first();
    $this->compliance->markStatus($check, ComplianceStatus::Rejected, $this->admin);

    expect($this->property->fresh()->is_verified)->toBeFalse()
        ->and($this->compliance->summary($this->property)['status'])->toBe('blocked');
});

it('éteint le badge quand un document arrive à échéance', function () {
    $this->property->complianceChecks()->update(['status' => ComplianceStatus::Verified]);
    $this->compliance->syncPropertyVerification($this->property);

    $this->property->complianceChecks()
        ->where('item', ComplianceItem::OwnerIdentity)
        ->update(['expires_on' => now()->subDay()]);

    $this->compliance->syncPropertyVerification($this->property);

    expect($this->property->fresh()->is_verified)->toBeFalse()
        ->and($this->property->complianceChecks()->where('item', ComplianceItem::OwnerIdentity)->first()->status)
        ->toBe(ComplianceStatus::Expired);
});

it('accepte qu\'une pièce conditionnelle soit sans objet', function () {
    $check = $this->property->complianceChecks()->where('item', ComplianceItem::TourismLicence)->first();

    $this->actingAs($this->admin)->patch(
        route('admin.villas.compliance.status', [$this->property, $check]),
        ['status' => ComplianceStatus::NotApplicable->value]
    )->assertRedirect();

    expect($check->fresh()->status)->toBe(ComplianceStatus::NotApplicable);
});

it('accepte le RCCM en sans objet, devenu conditionnel après le lancement', function () {
    $check = $this->property->complianceChecks()->where('item', ComplianceItem::BusinessRegistration)->first();

    $this->actingAs($this->admin)->patch(
        route('admin.villas.compliance.status', [$this->property, $check]),
        ['status' => ComplianceStatus::NotApplicable->value]
    )->assertRedirect();

    expect($check->fresh()->status)->toBe(ComplianceStatus::NotApplicable);
});

it('refuse d\'écarter une pièce obligatoire', function () {
    $check = $this->property->complianceChecks()->where('item', ComplianceItem::OwnerIdentity)->first();

    $this->actingAs($this->admin)->patch(
        route('admin.villas.compliance.status', [$this->property, $check]),
        ['status' => ComplianceStatus::NotApplicable->value]
    )->assertSessionHas('error');

    expect($check->fresh()->status)->not->toBe(ComplianceStatus::NotApplicable);
});

it('refuse de déclarer vérifiée une pièce sans document', function () {
    $check = $this->property->complianceChecks()->where('item', ComplianceItem::OwnershipProof)->first();

    $this->actingAs($this->admin)->patch(
        route('admin.villas.compliance.status', [$this->property, $check]),
        ['status' => ComplianceStatus::Verified->value]
    )->assertSessionHas('error');

    expect($check->fresh()->status)->not->toBe(ComplianceStatus::Verified);
});

it('accepte de vérifier un contrôle qui ne porte pas de document', function () {
    $check = $this->property->complianceChecks()->where('item', ComplianceItem::AddressVerified)->first();

    $this->actingAs($this->admin)->patch(
        route('admin.villas.compliance.status', [$this->property, $check]),
        ['status' => ComplianceStatus::Verified->value]
    )->assertRedirect();

    expect($check->fresh()->status)->toBe(ComplianceStatus::Verified)
        ->and($check->fresh()->verified_by)->toBe($this->admin->id);
});

it('supprime le fichier du disque quand la pièce est retirée', function () {
    Storage::fake('compliance');

    $check = $this->property->complianceChecks()->where('item', ComplianceItem::OwnerIdentity)->first();
    $this->actingAs($this->admin)->post(
        route('admin.villas.compliance.upload', [$this->property, $check]),
        ['document' => UploadedFile::fake()->create('piece.pdf', 20, 'application/pdf')]
    );

    $path = $check->fresh()->document_path;
    Storage::disk('compliance')->assertExists($path);

    $this->actingAs($this->admin)
        ->delete(route('admin.villas.compliance.document.destroy', [$this->property, $check]))
        ->assertRedirect();

    Storage::disk('compliance')->assertMissing($path);
    expect($check->fresh()->status)->toBe(ComplianceStatus::Pending);
});

it('construit le dossier sans doublon, même appelé plusieurs fois', function () {
    $this->compliance->ensureChecklist($this->property);
    $this->compliance->ensureChecklist($this->property);

    expect(ComplianceCheck::where('property_id', $this->property->id)->count())
        ->toBe(count(ComplianceItem::ordered()));
});

/*
|--------------------------------------------------------------------------
| Identité du propriétaire — des champs, jamais un document
|--------------------------------------------------------------------------
*/

it('enregistre le numéro de CNI sur le propriétaire, pas sur le contrôle', function () {
    $check = $this->property->complianceChecks()->where('item', ComplianceItem::OwnerIdentity)->first();

    $this->actingAs($this->admin)->patch(
        route('admin.villas.compliance.identity', [$this->property, $check]),
        ['status' => ComplianceStatus::Provided->value, 'cni_number' => '1 234 2020 56789']
    )->assertRedirect();

    expect($this->property->owner->fresh()->cni_number)->toBe('1 234 2020 56789')
        ->and($check->fresh()->status)->toBe(ComplianceStatus::Provided);
});

it('refuse de déclarer l\'identité vérifiée sans numéro de CNI', function () {
    $check = $this->property->complianceChecks()->where('item', ComplianceItem::OwnerIdentity)->first();

    $this->actingAs($this->admin)->patch(
        route('admin.villas.compliance.identity', [$this->property, $check]),
        ['status' => ComplianceStatus::Verified->value]
    )->assertSessionHas('error');

    expect($check->fresh()->status)->not->toBe(ComplianceStatus::Verified);
});

it('n\'exige aucun document pour l\'identité du propriétaire', function () {
    $check = $this->property->complianceChecks()->where('item', ComplianceItem::OwnerIdentity)->first();
    $this->property->owner->update(['cni_number' => '1 234 2020 56789']);

    $this->actingAs($this->admin)->patch(
        route('admin.villas.compliance.identity', [$this->property, $check]),
        ['status' => ComplianceStatus::Verified->value]
    )->assertRedirect();

    expect($check->fresh()->status)->toBe(ComplianceStatus::Verified)
        ->and($check->fresh()->hasDocument())->toBeFalse();
});
