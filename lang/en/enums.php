<?php

declare(strict_types=1);

return [

    'user_role' => [
        'admin' => 'Administrator',
        'customer' => 'Customer',
        'owner' => 'Owner',
    ],

    'property_status' => [
        'draft' => 'Draft',
        'published' => 'Published',
        'unpublished' => 'Unpublished',
        'suspended' => 'Suspended',
    ],

    'property_type' => [
        'villa' => 'Villa',
        'apartment' => 'Apartment',
        'residence' => 'Residence',
        'duplex' => 'Duplex',
    ],

    'owner_status' => [
        'active' => 'Active',
        'inactive' => 'Inactive',
    ],

    'block_reason' => [
        'booking' => 'Booking',
        'manual' => 'Manual block',
        'maintenance' => 'Maintenance',
        'owner_use' => 'Owner use',
    ],

    'booking_status' => [
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'cancelled' => 'Cancelled',
        'completed' => 'Completed',
        'refunded' => 'Refunded',
    ],

    'payment_status' => [
        'pending' => 'Pending',
        'processing' => 'Processing',
        'succeeded' => 'Succeeded',
        'failed' => 'Failed',
        'refunded' => 'Refunded',
        'partially_refunded' => 'Partially refunded',
    ],

    'transaction_type' => [
        'initiate' => 'Initiation',
        'webhook' => 'Webhook',
        'verify' => 'Verification',
        'refund' => 'Refund',
    ],

    'review_status' => [
        'pending' => 'Awaiting moderation',
        'approved' => 'Published',
        'rejected' => 'Rejected',
    ],

    'commission_status' => [
        'pending' => 'Outstanding',
        'settled' => 'Settled',
        'cancelled' => 'Cancelled',
    ],

    'promotion_type' => [
        'percentage' => 'Percentage',
        'fixed' => 'Fixed amount',
    ],

    'conversation_status' => [
        'open' => 'Open',
        'closed' => 'Closed',
    ],

    'compliance_item' => [
        'owner_identity' => 'Owner identity',
        'ownership_proof' => 'Proof of ownership / right to operate',
        'business_registration' => 'Trade register or operator details',
        'tourism_licence' => 'Tourism licence',
        'operating_permit' => 'Operating permit',
        'address_verified' => 'Address verified',
        'photos_verified' => 'Photos verified',
        'rental_terms' => 'Rental terms',
    ],

    'compliance_status' => [
        'pending' => 'Outstanding',
        'provided' => 'Submitted, awaiting review',
        'verified' => 'Verified',
        'not_applicable' => 'Not applicable',
        'rejected' => 'Rejected',
        'expired' => 'Expired',
    ],

];
