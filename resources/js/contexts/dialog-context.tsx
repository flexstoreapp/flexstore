import { createContext, type ReactNode, useContext } from 'react';

const DialogContext = createContext(false);

export function useInDialogLayer(): boolean {
    return useContext(DialogContext);
}

export function DialogLayerProvider({ children }: { children: ReactNode }) {
    return <DialogContext.Provider value={true}>{children}</DialogContext.Provider>;
}
