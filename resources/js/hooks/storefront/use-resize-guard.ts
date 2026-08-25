import { useEffect } from 'react';

export function useResizeGuard() {
    useEffect(() => {
        let timer: ReturnType<typeof setTimeout>;

        const handleResize = () => {
            document.documentElement.setAttribute('data-resizing', '');
            clearTimeout(timer);
            timer = setTimeout(() => {
                document.documentElement.removeAttribute('data-resizing');
            }, 150);
        };

        window.addEventListener('resize', handleResize);

        return () => {
            window.removeEventListener('resize', handleResize);
            clearTimeout(timer);
            document.documentElement.removeAttribute('data-resizing');
        };
    }, []);
}
