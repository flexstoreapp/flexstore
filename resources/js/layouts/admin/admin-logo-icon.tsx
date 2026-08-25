export function AdminLogoIcon(props: React.SVGAttributes<SVGElement>) {
    return (
        <svg {...props} xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
            <rect x="0" y="0" width="52" height="52" rx="12" ry="12" fill="currentColor" />
            <rect x="14" y="14" width="24" height="4" rx="2" ry="2" className="fill-white dark:fill-black" />
            <rect x="14" y="24" width="18" height="4" rx="2" ry="2" className="fill-white dark:fill-black" />
            <rect x="14" y="34" width="12" height="4" rx="2" ry="2" className="fill-white dark:fill-black" />
        </svg>
    );
}
