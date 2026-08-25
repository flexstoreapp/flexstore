<?php

declare(strict_types=1);

namespace App\Enums;

enum Locale: string
{
    case af = 'Afrikaans';
    case am = 'አማርኛ';
    case ar = 'العربية';
    case az = 'Azərbaycan';
    case be = 'Беларуская';
    case bg = 'Български';
    case bn = 'বাংলা';
    case bs = 'Bosanski';
    case ca = 'Català';
    case cs = 'Čeština';
    case cy = 'Cymraeg';
    case da = 'Dansk';
    case de = 'Deutsch';
    case el = 'Ελληνικά';
    case en = 'English';
    case es = 'Español';
    case et = 'Eesti';
    case eu = 'Euskara';
    case fa = 'فارسی';
    case fi = 'Suomi';
    case fil = 'Filipino';
    case fr = 'Français';
    case ga = 'Gaeilge';
    case gl = 'Galego';
    case gu = 'ગુજરાતી';
    case ha = 'Hausa';
    case hi = 'हिन्दी';
    case hr = 'Hrvatski';
    case hu = 'Magyar';
    case hy = 'Հայերեն';
    case id = 'Bahasa Indonesia';
    case ig = 'Igbo';
    case is = 'Íslenska';
    case it = 'Italiano';
    case ja = '日本語';
    case ka = 'ქართული';
    case kk = 'Қазақша';
    case km = 'ខ្មែរ';
    case kn = 'ಕನ್ನಡ';
    case ko = '한국어';
    case ku = 'Kurdî';
    case ky = 'Кыргызча';
    case lo = 'ລາວ';
    case lt = 'Lietuvių';
    case lv = 'Latviešu';
    case mk = 'Македонски';
    case ml = 'മലയാളം';
    case mn = 'Монгол';
    case mr = 'मराठी';
    case ms = 'Bahasa Melayu';
    case my = 'မြန်မာ';
    case nb = 'Norsk Bokmål';
    case ne = 'नेपाली';
    case nl = 'Nederlands';
    case nn = 'Norsk Nynorsk';
    case pa = 'ਪੰਜਾਬੀ';
    case pl = 'Polski';
    case ps = 'پښتو';
    case pt = 'Português';
    case ro = 'Română';
    case ru = 'Русский';
    case sd = 'سنڌي';
    case si = 'සිංහල';
    case sk = 'Slovenčina';
    case sl = 'Slovenščina';
    case so = 'Soomaali';
    case sq = 'Shqip';
    case sr = 'Српски';
    case sv = 'Svenska';
    case sw = 'Kiswahili';
    case ta = 'தமிழ்';
    case te = 'తెలుగు';
    case tg = 'Тоҷикӣ';
    case th = 'ไทย';
    case tk = 'Türkmen';
    case tr = 'Türkçe';
    case ug = 'ئۇيغۇرچە';
    case uk = 'Українська';
    case ur = 'اردو';
    case uz = 'Oʻzbek';
    case vi = 'Tiếng Việt';
    case yo = 'Yorùbá';
    case zh = '中文';
    case zu = 'IsiZulu';

    /** @var list<string> */
    private const array RTL_CODES = ['ar', 'fa', 'he', 'ur', 'ps', 'ku', 'sd', 'ug', 'yi'];

    public static function nativeName(string $code): string
    {
        $locale = collect(self::cases())->first(fn (self $case): bool => $case->name === $code);

        return $locale->value ?? $code;
    }

    public static function isRtl(string $code): bool
    {
        $base = mb_strtolower((string) strtok($code, '_-'));

        return in_array($base, self::RTL_CODES, true);
    }
}
