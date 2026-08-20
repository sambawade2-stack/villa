<?php

declare(strict_types=1);

return [

    'user_role' => [
        'admin' => 'Administrator',
        'customer' => 'Customer',
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

];
