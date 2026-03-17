import { Form, Head } from '@inertiajs/react';
import {
    edit,
    update,
} from '@/actions/App/Http/Controllers/Admin/PathwayConfigurationController';
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

type Config = {
    id: number;
    min_approved_games: number;
    max_rank: number;
    max_conduct_flags: number;
};

type Props = {
    config: Config | null;
};

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Pathway Configuration', href: edit().url },
];

export default function PathwayEdit({ config }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Pathway Configuration" />

            <div className="flex max-w-2xl flex-col gap-6 p-6">
                <div>
                    <h1 className="text-2xl font-semibold">
                        Pathway Configuration
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Define the criteria for pathway eligibility. A new
                        configuration is saved on each update and recalculation
                        is queued automatically.
                    </p>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Eligibility Criteria</CardTitle>
                        <CardDescription>
                            Players must meet all criteria to be considered
                            pathway candidates.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Form
                            {...update.form()}
                            className="flex flex-col gap-4"
                        >
                            {({ processing, errors }) => (
                                <>
                                    <div className="grid gap-2">
                                        <Label htmlFor="min_approved_games">
                                            Minimum Approved Games
                                        </Label>
                                        <Input
                                            id="min_approved_games"
                                            name="min_approved_games"
                                            type="number"
                                            min="1"
                                            defaultValue={
                                                config?.min_approved_games ?? ''
                                            }
                                            required
                                        />
                                        <InputError
                                            message={errors.min_approved_games}
                                        />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="max_rank">
                                            Maximum Rank (best rank required)
                                        </Label>
                                        <Input
                                            id="max_rank"
                                            name="max_rank"
                                            type="number"
                                            min="1"
                                            defaultValue={
                                                config?.max_rank ?? ''
                                            }
                                            required
                                        />
                                        <InputError message={errors.max_rank} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="max_conduct_flags">
                                            Maximum Conduct Flags
                                        </Label>
                                        <Input
                                            id="max_conduct_flags"
                                            name="max_conduct_flags"
                                            type="number"
                                            min="0"
                                            defaultValue={
                                                config?.max_conduct_flags ?? ''
                                            }
                                            required
                                        />
                                        <InputError
                                            message={errors.max_conduct_flags}
                                        />
                                    </div>

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
