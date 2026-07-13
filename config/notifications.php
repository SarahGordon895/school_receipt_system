<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Maximum parents per manual / bursar test send batch
    |--------------------------------------------------------------------------
    |
    | Bursar manual and report-triggered SMS/email sends are capped so testing
    | and bulk actions never message the entire school at once.
    |
    */
    'min_batch_parents' => 1,
    'max_batch_parents' => 5,
];
