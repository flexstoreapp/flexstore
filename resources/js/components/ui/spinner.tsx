import { LoaderIcon } from "lucide-react"

import { __ } from "@/lib/i18n"
import { cn } from "@/lib/utils"

function Spinner({ className, ...props }: React.ComponentProps<"svg">) {
    return (
        <LoaderIcon
            role="status"
            aria-label={__('Loading')}
            className={cn("size-4 animate-spin", className)}
            {...props}
        />
    )
}

export { Spinner }
