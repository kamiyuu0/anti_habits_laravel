// ライト(nord) / ダーク(dim) テーマの切り替え。
// ユーザーが選択済みなら localStorage の値を、未選択なら OS の設定 (prefers-color-scheme) に従う。
const STORAGE_KEY = 'theme';
const LIGHT = 'nord';
const DARK = 'dim';
const THEME_COLORS = { [LIGHT]: '#edeff4', [DARK]: '#2a303c' };
const darkQuery = window.matchMedia('(prefers-color-scheme: dark)');

export function isDark() {
    const theme = document.documentElement.dataset.theme;
    return theme ? theme === DARK : darkQuery.matches;
}

function syncThemeColor() {
    const meta = document.querySelector('meta[name="theme-color"]');
    if (meta) meta.content = THEME_COLORS[isDark() ? DARK : LIGHT];
}

function notify() {
    syncThemeColor();
    document.dispatchEvent(new CustomEvent('theme:changed'));
}

export function toggleTheme() {
    const next = isDark() ? LIGHT : DARK;
    document.documentElement.dataset.theme = next;
    try {
        localStorage.setItem(STORAGE_KEY, next);
    } catch (e) {
        // localStorage が使えない環境ではこのページ内のみ有効
    }
    notify();
}

// OS 側の設定変更に追従する (ユーザーが明示的に選択している場合は無視)
darkQuery.addEventListener('change', () => {
    if (!document.documentElement.dataset.theme) notify();
});
syncThemeColor();
