<?php

return [
    // Enable only after the additive migration and verified backup are ready.
    'encryption_enabled' => (bool) env('PERSONAL_DATA_ENCRYPTION_ENABLED', false),
];
