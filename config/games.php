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

    /*
    |--------------------------------------------------------------------------
    | Video Storage
    |--------------------------------------------------------------------------
    |
    | Game videos are stored on this server, on the "videos" disk. Vimeo is
    | the failover: an upload goes there when server uploads are switched
    | off, when the server would be left with less than the reserved free
    | space, or when the browser could not finish uploading to the server.
    |
    */

    'videos' => [
        'server_uploads' => (bool) env('GAME_VIDEO_SERVER_UPLOADS', true),
        'reserved_disk_space' => (int) env('GAME_VIDEO_RESERVED_DISK_SPACE', 1024 * 1024 * 1024),
    ],
];
