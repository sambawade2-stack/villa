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

    'compliance_item' => [
        'owner_identity' => 'Identité du propriétaire',
        'ownership_proof' => 'Justificatif de propriété / droit d\'exploitation',
        'business_registration' => 'RCCM ou informations de l\'exploitant',
        'tourism_licence' => 'Agrément touristique',
        'operating_permit' => 'Autorisation d\'exploitation',
        'address_verified' => 'Adresse vérifiée',
        'photos_verified' => 'Photos vérifiées',
        'rental_terms' => 'Conditions de location',
    ],

    'compliance_status' => [
        'pending' => 'À fournir',
        'provided' => 'Déposé, à contrôler',
        'verified' => 'Vérifié',
        'not_applicable' => 'Sans objet',
        'rejected' => 'Refusé',
        'expired' => 'Périmé',
    ],

];
