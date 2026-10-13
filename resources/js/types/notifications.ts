/** A database notification as shared with every page (see HandleInertiaRequests). */
export type AppNotification = {
    id: string;
    title: string;
    message: string;
    url: string | null;
    read_at: string | null;
    created_at: string | null;
};
