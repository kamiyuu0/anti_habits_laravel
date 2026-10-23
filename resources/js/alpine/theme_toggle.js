import { isDark, toggleTheme } from '../lib/theme';

// ヘッダーのダークモード切り替えボタン。
// ダーク中は太陽(ライトへ切り替え)、ライト中は月(ダークへ切り替え)を表示する
export default () => ({
    dark: isDark(),

    toggle() {
        toggleTheme();
    },

    sync() {
        this.dark = isDark();
    },
});
