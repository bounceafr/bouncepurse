type NamedCourt = {
    name: string;
    category_label?: string | null;
};

export function formatCourtName(court: NamedCourt | null | undefined): string {
    if (!court) {
        return '—';
    }

    if (!court.category_label) {
        return court.name;
    }

    return `${court.name} — ${court.category_label}`;
}
