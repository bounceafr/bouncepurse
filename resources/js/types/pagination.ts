export type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

/** A Laravel LengthAwarePaginator serialised to JSON. */
export type Paginated<T> = {
    data: T[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    total: number;
};
