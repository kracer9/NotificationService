<?php

return [
    'worker_by_priority' => [
        'high' => [
            'tries' => (int) env('HIGH_PRIORITY_TRIES', 5),
            'backoff' => (int) env('HIGH_PRIORITY_DELAY', 3),
        ],
        'low' => [
            'tries' => (int) env('LOW_PRIORITY_TRIES', 3),
            'backoff' => (int) env('LOW_PRIORITY_DELAY', 10),
        ],
    ],
];
