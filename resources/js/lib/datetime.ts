const pad = (value: number) => String(value).padStart(2, '0');

/**
 * ISO-8601 timestamp in the viewer's local time with an explicit UTC offset,
 * e.g. 2026-10-13T11:55:00+02:00, so the server never has to guess the timezone.
 */
export function toOffsetIso(date: Date): string {
    const offset = -date.getTimezoneOffset();
    const sign = offset >= 0 ? '+' : '-';
    const absolute = Math.abs(offset);

    return (
        `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}` +
        `T${pad(date.getHours())}:${pad(date.getMinutes())}:${pad(date.getSeconds())}` +
        `${sign}${pad(Math.floor(absolute / 60))}:${pad(absolute % 60)}`
    );
}

/** Convert a datetime-local input value (local wall time, no offset) to toOffsetIso format. */
export function localInputToOffsetIso(value: string): string {
    if (!value) return value;

    const date = new Date(value);

    return Number.isNaN(date.getTime()) ? value : toOffsetIso(date);
}

/** Combine a picked calendar day with an HH:mm time in the viewer's local timezone. */
export function combineLocalDateAndTime(date: Date, time: string): string {
    const [hours, minutes] = time.split(':').map(Number);
    const combined = new Date(date);
    combined.setHours(hours, minutes, 0, 0);

    return toOffsetIso(combined);
}
