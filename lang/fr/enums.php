<?php

declare(strict_types=1);

return [

    'user_role' => [
        'admin' => 'Administrateur',
        'customer' => 'Client',
    ],

    'property_status' => [
        'draft' => 'Brouillon',
        'published' => 'Publiée',
        'unpublished' => 'Dépubliée',
        'suspended' => 'Suspendue',
    ],

    'property_type' => [
        'villa' => 'Villa',
        'apartment' => 'Appartement',
        'residence' => 'Résidence',
        'duplex' => 'Duplex',
    ],

    'owner_status' => [
        'active' => 'Actif',
        'inactive' => 'Inactif',
    ],

    'block_reason' => [
        'booking' => 'Réservation',
        'manual' => 'Blocage manuel',
        'maintenance' => 'Maintenance',
    ],

    'booking_status' => [
        'pending' => 'En attente',
        'confirmed' => 'Confirmée',
        'cancelled' => 'Annulée',
        'completed' => 'Terminée',
        'refunded' => 'Remboursée',
    ],

    'payment_status' => [
        'pending' => 'En attente',
        'processing' => 'En cours',
        'succeeded' => 'Réussi',
        'failed' => 'Échoué',
        'refunded' => 'Remboursé',
        'partially_refunded' => 'Partiellement remboursé',
    ],

    'transaction_type' => [
        'initiate' => 'Initialisation',
        'webhook' => 'Notification serveur',
        'verify' => 'Vérification',
        'refund' => 'Remboursement',
    ],

    'review_status' => [
        'pending' => 'En modération',
        'approved' => 'Publié',
        'rejected' => 'Rejeté',
    ],

    'commission_status' => [
        'pending' => 'À reverser',
        'settled' => 'Reversée',
        'cancelled' => 'Annulée',
    ],

    'promotion_type' => [
        'percentage' => 'Pourcentage',
        'fixed' => 'Montant fixe',
    ],

    'conversation_status' => [
        'open' => 'Ouverte',
        'closed' => 'Fermée',
    ],

];
