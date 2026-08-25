import { Head, Link, router } from '@inertiajs/react';
import { CheckCircle2Icon, MinusCircleIcon } from 'lucide-react';

import { StatusIcon } from '@/components/installer/status-icon';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';

interface Extension {
    name: string;
    passed: boolean;
}

interface Driver {
    name: string;
    label: string;
    passed: boolean;
}

interface Directory {
    path: string;
    passed: boolean;
}

interface RequirementsProps {
    php: { required: string; current: string; passed: boolean };
    extensions: Extension[];
    drivers: Driver[];
    directories: Directory[];
    envWritable: boolean;
    canProceed: boolean;
}

export default function Requirements({
    php,
    extensions,
    drivers,
    directories,
    envWritable,
    canProceed,
}: RequirementsProps) {
    const failedExtensions = extensions.filter((e) => !e.passed);
    const failedDirectories = directories.filter((d) => !d.passed);
    const hasAnyDriver = drivers.some((d) => d.passed);

    return (
        <>
            <Head title="Requirements" />

            <Card>
                <CardHeader>
                    <CardTitle>Server requirements</CardTitle>
                    <CardDescription>
                        Checking if your server meets the minimum requirements to run FlexStore
                    </CardDescription>
                </CardHeader>

                <CardContent className="space-y-6">
                    <div className="space-y-2">
                        <div className="flex items-center justify-between">
                            <span className="text-sm font-medium">PHP version</span>
                            <div className="flex items-center gap-2">
                                <Badge variant={php.passed ? 'secondary' : 'destructive'}>{php.current}</Badge>
                                <StatusIcon passed={php.passed} />
                            </div>
                        </div>
                        {!php.passed && (
                            <p className="text-sm text-destructive">
                                PHP {php.required} or higher is required. Your server is running PHP {php.current}.
                            </p>
                        )}
                    </div>

                    <div className="space-y-2">
                        <h3 className="text-sm font-medium">
                            PHP extensions
                            {failedExtensions.length > 0 && (
                                <span className="ms-1.5 text-xs font-normal text-destructive">
                                    ({failedExtensions.length} missing)
                                </span>
                            )}
                        </h3>
                        <div className="grid grid-cols-2 gap-x-6 gap-y-1.5">
                            {extensions.map((ext) => (
                                <div key={ext.name} className="flex items-center justify-between text-sm">
                                    <code className="text-muted-foreground">{ext.name}</code>
                                    <StatusIcon passed={ext.passed} />
                                </div>
                            ))}
                        </div>
                    </div>

                    <div className="space-y-2">
                        <h3 className="text-sm font-medium">
                            Database drivers
                            <span className="ms-1.5 text-xs font-normal text-muted-foreground">
                                (at least one required)
                            </span>
                        </h3>
                        <div className="space-y-1.5">
                            {drivers.map((driver) => (
                                <div key={driver.name} className="flex items-center justify-between text-sm">
                                    <span className="text-muted-foreground">
                                        {driver.label}
                                        <code className="ms-1.5 text-xs">({driver.name})</code>
                                    </span>
                                    {driver.passed ? (
                                        <CheckCircle2Icon className="size-4 shrink-0 text-emerald-700" />
                                    ) : (
                                        <MinusCircleIcon className="size-4 shrink-0 text-muted-foreground/50" />
                                    )}
                                </div>
                            ))}
                        </div>
                        {!hasAnyDriver && (
                            <p className="text-sm text-destructive">
                                At least one database driver (pdo_mysql, pdo_pgsql, or pdo_sqlite) must be installed.
                            </p>
                        )}
                    </div>

                    <div className="space-y-2">
                        <h3 className="text-sm font-medium">
                            Write permissions
                            {(!envWritable || directories.some((d) => !d.passed)) && (
                                <span className="ms-1.5 text-xs font-normal text-destructive">
                                    (
                                    {
                                        [!envWritable && '.env', ...failedDirectories.map((d) => d.path)].filter(
                                            Boolean,
                                        ).length
                                    }{' '}
                                    not writable)
                                </span>
                            )}
                        </h3>
                        <div className="space-y-1.5">
                            <div className="flex items-center justify-between text-sm">
                                <code className="text-muted-foreground">.env</code>
                                <StatusIcon passed={envWritable} />
                            </div>
                            {directories.map((dir) => (
                                <div key={dir.path} className="flex items-center justify-between text-sm">
                                    <code className="text-muted-foreground">{dir.path}</code>
                                    <StatusIcon passed={dir.passed} />
                                </div>
                            ))}
                        </div>
                    </div>
                </CardContent>

                <CardFooter className="justify-between border-t">
                    {canProceed ? (
                        <>
                            <p className="text-sm text-emerald-700">All requirements met.</p>
                            <Button size="md" onClick={() => router.post('/install')}>
                                Continue
                            </Button>
                        </>
                    ) : (
                        <>
                            <p className="text-sm text-destructive">Please fix the issues above before continuing.</p>
                            <Button asChild variant="outline" className="cursor-default">
                                <Link href="/install">Re-check</Link>
                            </Button>
                        </>
                    )}
                </CardFooter>
            </Card>
        </>
    );
}

Requirements.layout = { step: 1 };
