import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    Drawer,
    DrawerClose,
    DrawerContent,
    DrawerDescription,
    DrawerFooter,
    DrawerHeader,
    DrawerTitle,
} from '@/components/ui/drawer';
import { useIsMobile } from '@/hooks/admin/use-mobile';
import { cn } from '@/lib/utils';

export function AdaptiveDialog({ ...props }: React.ComponentProps<typeof Dialog>) {
    const isMobile = useIsMobile();

    return isMobile ? (
        <Drawer shouldScaleBackground={true} repositionInputs={false} {...props} />
    ) : (
        <Dialog {...props} />
    );
}

export function AdaptiveDialogContent({ children, className, ...props }: React.ComponentProps<typeof DialogContent>) {
    const isMobile = useIsMobile();

    return isMobile ? (
        <DrawerContent {...props}>
            <div className="overflow-auto">{children}</div>
        </DrawerContent>
    ) : (
        <DialogContent className={cn("max-h-full min-w-0 overflow-auto [&>*]:min-w-0", className)} showCloseButton={false} {...props}>
            {children}
        </DialogContent>
    );
}

export function AdaptiveDialogContentContainer({
    className,
    children,
    ...props
}: React.PropsWithChildren<React.HTMLAttributes<HTMLDivElement>>) {
    return (
        <div className={cn("min-w-0 space-y-6 px-6 py-2 md:px-0 md:py-6", className)} {...props}>
            {children}
        </div>
    );
}

export function AdaptiveDialogHeader({ ...props }: React.ComponentProps<typeof DialogHeader>) {
    const isMobile = useIsMobile();

    return isMobile ? <DrawerHeader {...props} /> : <DialogHeader {...props} />;
}

export function AdaptiveDialogFooter({ children, ...props }: React.ComponentProps<typeof DialogFooter>) {
    const isMobile = useIsMobile();

    return isMobile ? (
        <DrawerFooter {...props}>{children}</DrawerFooter>
    ) : (
        <DialogFooter {...props}>{children}</DialogFooter>
    );
}

export function AdaptiveDialogTitle({ ...props }: React.ComponentProps<typeof DialogTitle>) {
    const isMobile = useIsMobile();

    return isMobile ? <DrawerTitle {...props} /> : <DialogTitle {...props} />;
}

export function AdaptiveDialogDescription({ ...props }: React.ComponentProps<typeof DialogDescription>) {
    const isMobile = useIsMobile();

    return isMobile ? <DrawerDescription {...props} /> : <DialogDescription {...props} />;
}

export function AdaptiveDialogClose({ ...props }: React.ComponentProps<typeof DialogClose>) {
    const isMobile = useIsMobile();

    return isMobile ? <DrawerClose {...props} /> : <DialogClose {...props} />;
}
