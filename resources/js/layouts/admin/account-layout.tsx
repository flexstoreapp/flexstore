import { Link, usePage } from '@inertiajs/react';

import * as PasswordController from '@/actions/App/Http/Controllers/Admin/PasswordController';
import * as ProfileController from '@/actions/App/Http/Controllers/Admin/ProfileController';
import * as SecurityController from '@/actions/App/Http/Controllers/Admin/SecurityController';
import { Heading } from '@/components/admin/heading';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { __ } from '@/lib/i18n';
import { cn } from '@/lib/utils';
import type { NavItem } from '@/types';

const sidebarNavItems: NavItem[] = [
    {
        title: __('Profile'),
        href: ProfileController.edit(),
    },
    {
        title: __('Password'),
        href: PasswordController.edit(),
    },
    {
        title: __('Security'),
        href: SecurityController.show(),
    },
];

export function AccountLayout({ children }: React.PropsWithChildren) {
    const page = usePage();

    return (
        <>
            <Heading title={__('Account')} description={__('Manage your profile and account settings')} />

            <div className="flex flex-col lg:flex-row lg:gap-12">
                <aside className="w-full max-w-xl lg:w-48">
                    <nav aria-label={__('Account settings')} className="flex flex-col space-y-1 space-x-0">
                        {sidebarNavItems.map((item) => (
                            <Button
                                key={item.title}
                                variant="ghost"
                                asChild
                                className={cn('w-full justify-start hover:bg-accent/50', {
                                    'bg-accent hover:bg-accent': page.url === item.href?.url,
                                })}
                            >
                                <Link href={item.href!} prefetch>
                                    {item.icon && <item.icon className="size-4" />}
                                    {item.title}
                                </Link>
                            </Button>
                        ))}
                    </nav>
                </aside>

                <Separator className="my-6 lg:hidden" />

                <div className="flex-1 md:max-w-2xl">
                    <section className="max-w-xl space-y-12">{children}</section>
                </div>
            </div>
        </>
    );
}
