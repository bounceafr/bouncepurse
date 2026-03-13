import { Form, Head } from '@inertiajs/react';
import {
    edit,
    update,
} from '@/actions/App/Http/Controllers/Admin/AllocationConfigurationController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

type Category = {
    value: string;
    label: string;
};

type Config = Record<string, number> & {
    id: number;
};

type Props = {
    config: Config;
    categories: Category[];
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Allocation Configuration', href: edit().url },
];

export default function AllocationConfigurationEdit({ config, categories }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Allocation Configuration" />

            <div className="flex max-w-2xl flex-col gap-6 p-6">
                <div>
                    <h1 className="text-2xl font-semibold">
                        Allocation Configuration
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Set the percentage split for the $1 per game allocation.
                        Percentages must sum to 100. A new configuration is
                        saved on each update; existing allocations are
                        unaffected.
                    </p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Percentage Split</CardTitle>
                        <CardDescription>
                            Each approved game allocates $1 split across these
                            five categories.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Form
                            {...update.form()}
                            className="flex flex-col gap-4"
                        >
                            {({ processing, errors }) => (
                                <>
                                    {categories.map((cat) => {
                                        const field = `${cat.value}_percentage`;

                                        return (
                                            <div
                                                key={cat.value}
                                                className="grid gap-2"
                                            >
                                                <Label htmlFor={field}>
                                                    {cat.label} (%)
                                                </Label>
                                                <Input
                                                    id={field}
                                                    name={field}
                                                    type="number"
                                                    step="0.01"
                                                    min="0"
                                                    max="100"
                                                    defaultValue={
                                                        config[field]
                                                    }
                                                    required
                                                />
                                                <InputError
                                                    message={errors[field]}
                                                />
                                            </div>
                                        );
                                    })}

                                    <Button
                                        disabled={processing}
                                        asChild
                                        className="w-fit"
                                    >
                                        <button type="submit">
                                            Save Configuration
                                        </button>
                                    </Button>
                                </>
                            )}
                        </Form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
