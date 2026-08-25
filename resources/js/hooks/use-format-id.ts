export function useFormatId() {
    return (id: number | string) => `\u2066#${id}\u2069`;
}
