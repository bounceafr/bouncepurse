import {
    DndContext,
    type DragEndEvent,
    KeyboardSensor,
    PointerSensor,
    closestCenter,
    useSensor,
    useSensors,
} from '@dnd-kit/core';
import {
    SortableContext,
    arrayMove,
    sortableKeyboardCoordinates,
    useSortable,
    verticalListSortingStrategy,
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import { Link } from '@inertiajs/react';
import {
    type ColumnDef,
    flexRender,
    getCoreRowModel,
    getSortedRowModel,
    type RowSelectionState,
    type SortingState,
    type VisibilityState,
    useReactTable,
} from '@tanstack/react-table';
import {
    ArrowDown,
    ArrowUp,
    ArrowUpDown,
    GripVertical,
    Settings2,
} from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

type PaginationLink = { url: string | null; label: string; active: boolean };

export function LaravelPagination({ links }: { links: PaginationLink[] }) {
    return (
        <div className="flex items-center justify-center gap-1">
            {links.map((link, i) => (
                <Button
                    key={i}
                    variant={link.active ? 'default' : 'outline'}
                    size="sm"
                    disabled={link.url === null}
                    asChild={link.url !== null}
                >
                    {link.url !== null ? (
                        <Link
                            href={link.url}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    ) : (
                        <span
                            dangerouslySetInnerHTML={{ __html: link.label }}
                        />
                    )}
                </Button>
            ))}
        </div>
    );
}

export function sortableHeader(label: string) {
    return ({ column }: { column: { getIsSorted: () => false | 'asc' | 'desc'; toggleSorting: (asc: boolean) => void } }) => (
        <Button
            variant="ghost"
            className="-ml-3 h-8 px-3"
            onClick={() =>
                column.toggleSorting(column.getIsSorted() === 'asc')
            }
        >
            {label}
            {column.getIsSorted() === 'asc' ? (
                <ArrowUp className="ml-2 size-3.5" />
            ) : column.getIsSorted() === 'desc' ? (
                <ArrowDown className="ml-2 size-3.5" />
            ) : (
                <ArrowUpDown className="ml-2 size-3.5 text-muted-foreground" />
            )}
        </Button>
    );
}

export function selectionColumn<TData>(): ColumnDef<TData, unknown> {
    return {
        id: 'select',
        header: ({ table }) => (
            <Checkbox
                checked={
                    table.getIsAllPageRowsSelected() ||
                    (table.getIsSomePageRowsSelected() && 'indeterminate')
                }
                onCheckedChange={(value) =>
                    table.toggleAllPageRowsSelected(!!value)
                }
                aria-label="Select all"
            />
        ),
        cell: ({ row }) => (
            <Checkbox
                checked={row.getIsSelected()}
                onCheckedChange={(value) => row.toggleSelected(!!value)}
                aria-label="Select row"
            />
        ),
        enableSorting: false,
        enableHiding: false,
    };
}

interface DataTableProps<TData> {
    columns: ColumnDef<TData, unknown>[];
    data: TData[];
    toolbar?: React.ReactNode;
    pagination?: React.ReactNode;
    draggable?: boolean;
    onRowOrderChange?: (newData: TData[]) => void;
    getRowId?: (row: TData) => string;
}

function SortableRow({
    id,
    children,
    isSelected,
}: {
    id: string;
    children: React.ReactNode;
    isSelected: boolean;
}) {
    const {
        attributes,
        listeners,
        setNodeRef,
        transform,
        transition,
        isDragging,
    } = useSortable({ id });

    const style: React.CSSProperties = {
        transform: CSS.Transform.toString(transform),
        transition,
        opacity: isDragging ? 0.5 : 1,
        position: 'relative',
        zIndex: isDragging ? 1 : 0,
    };

    return (
        <TableRow ref={setNodeRef} style={style} data-state={isSelected && 'selected'}>
            <TableCell className="w-8">
                <button
                    {...attributes}
                    {...listeners}
                    className="cursor-grab touch-none text-muted-foreground active:cursor-grabbing"
                    aria-label="Drag to reorder"
                >
                    <GripVertical className="size-4" />
                </button>
            </TableCell>
            {children}
        </TableRow>
    );
}

export function DataTable<TData>({
    columns,
    data,
    toolbar,
    pagination,
    draggable = false,
    onRowOrderChange,
    getRowId,
}: DataTableProps<TData>) {
    const [sorting, setSorting] = useState<SortingState>([]);
    const [columnVisibility, setColumnVisibility] = useState<VisibilityState>(
        {},
    );
    const [rowSelection, setRowSelection] = useState<RowSelectionState>({});
    const [draggableRows, setDraggableRows] = useState<TData[]>(data);

    const tableData = draggable ? draggableRows : data;

    // eslint-disable-next-line react-hooks/incompatible-library
    const table = useReactTable({
        data: tableData,
        columns,
        getCoreRowModel: getCoreRowModel(),
        getSortedRowModel: getSortedRowModel(),
        onSortingChange: setSorting,
        onColumnVisibilityChange: setColumnVisibility,
        onRowSelectionChange: setRowSelection,
        state: {
            sorting,
            columnVisibility,
            rowSelection,
        },
        getRowId,
    });

    const sensors = useSensors(
        useSensor(PointerSensor),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates,
        }),
    );

    function handleDragEnd(event: DragEndEvent) {
        const { active, over } = event;
        if (over && active.id !== over.id) {
            setDraggableRows((items) => {
                const rowModels = table.getRowModel().rows;
                const oldIndex = rowModels.findIndex(
                    (row) => row.id === active.id,
                );
                const newIndex = rowModels.findIndex(
                    (row) => row.id === over.id,
                );
                const newItems = arrayMove(items, oldIndex, newIndex);
                onRowOrderChange?.(newItems);
                return newItems;
            });
        }
    }

    const rowIds = table.getRowModel().rows.map((row) => row.id);

    const emptyCell = (
        <TableRow>
            <TableCell
                colSpan={columns.length + (draggable ? 1 : 0)}
                className="py-8 text-center text-muted-foreground"
            >
                No results.
            </TableCell>
        </TableRow>
    );

    const tableBody = (
        <TableBody>
            {table.getRowModel().rows.length ? (
                table.getRowModel().rows.map((row) =>
                    draggable ? (
                        <SortableRow
                            key={row.id}
                            id={row.id}
                            isSelected={row.getIsSelected()}
                        >
                            {row.getVisibleCells().map((cell) => (
                                <TableCell key={cell.id}>
                                    {flexRender(
                                        cell.column.columnDef.cell,
                                        cell.getContext(),
                                    )}
                                </TableCell>
                            ))}
                        </SortableRow>
                    ) : (
                        <TableRow
                            key={row.id}
                            data-state={row.getIsSelected() && 'selected'}
                        >
                            {row.getVisibleCells().map((cell) => (
                                <TableCell key={cell.id}>
                                    {flexRender(
                                        cell.column.columnDef.cell,
                                        cell.getContext(),
                                    )}
                                </TableCell>
                            ))}
                        </TableRow>
                    ),
                )
            ) : (
                emptyCell
            )}
        </TableBody>
    );

    return (
        <div className="flex flex-col gap-4">
            <div className="flex items-center gap-2">
                {toolbar}
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <Button
                            variant="outline"
                            size="sm"
                            className="ml-auto"
                        >
                            <Settings2 className="mr-2 size-4" />
                            Columns
                        </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        {table
                            .getAllColumns()
                            .filter((col) => col.getCanHide())
                            .map((col) => (
                                <DropdownMenuCheckboxItem
                                    key={col.id}
                                    className="capitalize"
                                    checked={col.getIsVisible()}
                                    onCheckedChange={(value) =>
                                        col.toggleVisibility(!!value)
                                    }
                                >
                                    {col.id}
                                </DropdownMenuCheckboxItem>
                            ))}
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>

            <div className="rounded-md border">
                <Table>
                    <TableHeader>
                        {table.getHeaderGroups().map((headerGroup) => (
                            <TableRow key={headerGroup.id}>
                                {draggable && <TableHead className="w-8" />}
                                {headerGroup.headers.map((header) => (
                                    <TableHead key={header.id}>
                                        {header.isPlaceholder
                                            ? null
                                            : flexRender(
                                                  header.column.columnDef
                                                      .header,
                                                  header.getContext(),
                                              )}
                                    </TableHead>
                                ))}
                            </TableRow>
                        ))}
                    </TableHeader>

                    {draggable ? (
                        <DndContext
                            sensors={sensors}
                            collisionDetection={closestCenter}
                            onDragEnd={handleDragEnd}
                        >
                            <SortableContext
                                items={rowIds}
                                strategy={verticalListSortingStrategy}
                            >
                                {tableBody}
                            </SortableContext>
                        </DndContext>
                    ) : (
                        tableBody
                    )}
                </Table>
            </div>

            {pagination && (
                <div className="flex justify-center">{pagination}</div>
            )}
        </div>
    );
}
