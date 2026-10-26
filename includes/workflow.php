<?php

/** Every status a document request can hold, in the order they normally occur. */
const REQUEST_STATUSES = [
    'Submitted',
    'Under Review',
    'Approved',
    'Processing',
    'Ready for Release',
    'Released',
    'Rejected',
    'Cancelled',
];

/** Statuses that still need registrar attention. */
const OPEN_REQUEST_STATUSES = [
    'Submitted',
    'Under Review',
    'Approved',
    'Processing',
    'Ready for Release',
];

/**
 * The only status changes the system allows. Both the registrar forms and the
 * server-side action handlers read from this map, so the rules live in one place.
 */
function statusTransitions(): array
{
    return [
        'Submitted' => ['Under Review', 'Rejected', 'Cancelled'],
        'Under Review' => ['Approved', 'Rejected'],
        'Approved' => ['Processing', 'Cancelled'],
        'Processing' => ['Ready for Release'],
        'Ready for Release' => ['Released'],
        'Released' => [],
        'Rejected' => [],
        'Cancelled' => [],
    ];
}

/** Returns the statuses a request in $status may move to next. */
function nextStatusesFor(string $status): array
{
    return statusTransitions()[$status] ?? [];
}

/** Returns true when a request may move from $from to $to. */
function canTransition(string $from, string $to): bool
{
    return in_array($to, nextStatusesFor($from), true);
}
