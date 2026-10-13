<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Result Confirmation
    |--------------------------------------------------------------------------
    |
    | After a result is submitted the opponent has a limited time to confirm or
    | reject it. They are reminded once, and if they still have not answered
    | the result goes to a moderator as contested so the game cannot stall.
    |
    */

    'result_confirmation' => [
        'remind_after_hours' => (int) env('GAME_RESULT_REMIND_AFTER_HOURS', 48),
        'expire_after_hours' => (int) env('GAME_RESULT_EXPIRE_AFTER_HOURS', 120),
    ],
];
