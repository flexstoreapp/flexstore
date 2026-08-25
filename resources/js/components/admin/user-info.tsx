import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/hooks/use-initials';
import type { User } from '@/types';

interface UserInfoProps {
    user: User | null;
    showEmail?: boolean;
}

export function UserInfo({ user, showEmail = false }: UserInfoProps) {
    const getInitials = useInitials();

    return (
        <>
            <Avatar className="h-8 w-8 overflow-hidden rounded-full">
                <AvatarImage src={user!.avatar} alt={user!.name} />
                <AvatarFallback className="rounded-lg bg-muted">{getInitials(user!.name)}</AvatarFallback>
            </Avatar>
            <div className="grid flex-1 gap-0.5 text-start text-sm">
                <span className="truncate font-medium">{user!.name}</span>
                {showEmail && <span className="truncate text-xs text-muted-foreground">{user!.email}</span>}
            </div>
        </>
    );
}
