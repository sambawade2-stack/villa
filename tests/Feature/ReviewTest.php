<?php

declare(strict_types=1);

use App\Enums\ReviewStatus;
use App\Models\Booking;
use App\Models\Property;
use App\Models\Review;

it('interdit deux avis sur la même réservation', function () {
    $review = Review::factory()->create();

    expect(sqlStateOf(fn () => Review::factory()->create([
        'booking_id' => $review->booking_id,
        'property_id' => $review->property_id,
        'user_id' => $review->user_id,
    ])))->toBe(UNIQUE_VIOLATION);
});

it('refuse une note hors de l échelle 1–5', function () {
    expect(sqlStateOf(fn () => Review::factory()->create(['cleanliness' => 6])))
        ->toBe(CHECK_VIOLATION);
});

it('calcule la note globale comme moyenne des cinq critères', function () {
    $overall = Review::computeOverall([
        'cleanliness' => 5, 'location' => 4, 'communication' => 5,
        'amenities' => 3, 'value_for_money' => 4,
    ]);

    expect($overall)->toBe(4.2);
});

it('met à jour les agrégats de la villa à la publication', function () {
    $property = Property::factory()->published()->create();

    // fresh() : les valeurs par défaut de PostgreSQL ne sont pas rapatriées
    // dans l'instance renvoyée par create().
    expect($property->fresh()->rating_avg)->toBeNull()
        ->and($property->fresh()->reviews_count)->toBe(0);

    Review::factory()->count(3)->create(['property_id' => $property->id]);

    $property->refresh();

    expect($property->reviews_count)->toBe(3)
        ->and((float) $property->rating_avg)->toBeGreaterThan(0);
});

it('ne compte pas les avis en attente de modération', function () {
    $property = Property::factory()->published()->create();

    Review::factory()->count(2)->create(['property_id' => $property->id]);
    Review::factory()->pending()->create(['property_id' => $property->id]);

    expect($property->fresh()->reviews_count)->toBe(2);
});

it('recalcule les agrégats après suppression', function () {
    $property = Property::factory()->published()->create();
    $reviews = Review::factory()->count(3)->create(['property_id' => $property->id]);

    $reviews->first()->delete();

    expect($property->fresh()->reviews_count)->toBe(2);
});

it('remet la note à nul quand il ne reste aucun avis', function () {
    $property = Property::factory()->published()->create();
    $review = Review::factory()->create(['property_id' => $property->id]);

    $review->delete();

    expect($property->fresh()->rating_avg)->toBeNull()
        ->and($property->fresh()->reviews_count)->toBe(0);
});

it("n'ouvre le droit à l'avis qu'après un séjour terminé", function () {
    $pending = Booking::factory()->create();
    $confirmed = Booking::factory()->confirmed()->create();
    $completed = Booking::factory()->completed()->create();

    expect($pending->acceptsReview())->toBeFalse()
        ->and($confirmed->acceptsReview())->toBeFalse()
        ->and($completed->acceptsReview())->toBeTrue();
});

it("referme le droit à l'avis une fois celui-ci déposé", function () {
    $review = Review::factory()->create();

    expect($review->booking->fresh()->acceptsReview())->toBeFalse();
});

it('ne publie que les avis approuvés', function () {
    $property = Property::factory()->published()->create();

    Review::factory()->count(2)->create(['property_id' => $property->id]);
    Review::factory()->pending()->create(['property_id' => $property->id]);
    Review::factory()->create(['property_id' => $property->id, 'status' => ReviewStatus::Rejected]);

    expect($property->publishedReviews()->count())->toBe(2)
        ->and($property->reviews()->count())->toBe(4);
});
