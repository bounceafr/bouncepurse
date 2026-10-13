<?php

declare(strict_types=1);

namespace App\Concerns;

use Illuminate\Support\Facades\Date;

/**
 * For form requests whose timestamps come from the viewer's local clock: they must carry
 * an explicit UTC offset, and validated() hands them back as UTC so storage never guesses.
 */
trait NormalizesTimestamps
{
    /** ISO-8601 with an offset, e.g. 2026-10-13T12:00:00+02:00 or 2026-10-13T10:00:00Z. */
    protected const string TIMESTAMP_FORMAT = 'Y-m-d\TH:i:sP';

    /** @return list<string> */
    abstract protected function timestampFields(): array;

    /**
     * @param  array-key|null  $key
     */
    public function validated($key = null, $default = null): mixed
    {
        /** @var array<string, mixed> $data */
        $data = parent::validated();

        foreach ($this->timestampFields() as $field) {
            if (is_string($data[$field] ?? null)) {
                $data[$field] = Date::parse($data[$field])->utc()->toDateTimeString();
            }
        }

        return data_get($data, $key, $default);
    }

    protected function timestampRule(): string
    {
        return 'date_format:'.self::TIMESTAMP_FORMAT;
    }
}
