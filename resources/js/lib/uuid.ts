const HEX = Array.from({ length: 256 }, (_, i) => i.toString(16).padStart(2, '0'));

export function uuidv7(): string {
    const bytes = new Uint8Array(16);
    crypto.getRandomValues(bytes);

    const timestamp = Date.now();
    bytes[0] = Math.floor(timestamp / 2 ** 40) & 0xff;
    bytes[1] = Math.floor(timestamp / 2 ** 32) & 0xff;
    bytes[2] = Math.floor(timestamp / 2 ** 24) & 0xff;
    bytes[3] = Math.floor(timestamp / 2 ** 16) & 0xff;
    bytes[4] = Math.floor(timestamp / 2 ** 8) & 0xff;
    bytes[5] = timestamp & 0xff;

    bytes[6] = (bytes[6] & 0x0f) | 0x70;
    bytes[8] = (bytes[8] & 0x3f) | 0x80;

    const hex = Array.from(bytes, (byte) => HEX[byte]);

    return [
        hex.slice(0, 4).join(''),
        hex.slice(4, 6).join(''),
        hex.slice(6, 8).join(''),
        hex.slice(8, 10).join(''),
        hex.slice(10, 16).join(''),
    ].join('-');
}
