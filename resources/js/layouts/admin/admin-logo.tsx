import { AdminLogoIcon } from './admin-logo-icon';

export function AdminLogo() {
    return (
        <>
            <div className="flex aspect-square size-8 shrink-0 items-center justify-center">
                <AdminLogoIcon className="size-full" />
            </div>
            <div className="grid flex-1 text-start leading-tight">
                <span className="truncate text-2xl font-semibold tracking-tight">
                    flex<span className="font-normal">store</span>
                </span>
            </div>
        </>
    );
}
