import { Controller } from '@hotwired/stimulus';
import { isDark, toggleTheme } from '../lib/theme';

// ヘッダーのダークモード切り替えボタン
export default class extends Controller {
    static targets = ['sun', 'moon'];

    connect() {
        this.render = this.render.bind(this);
        document.addEventListener('theme:changed', this.render);
        this.render();
    }

    disconnect() {
        document.removeEventListener('theme:changed', this.render);
    }

    toggle() {
        toggleTheme();
    }

    // ダーク中は太陽(ライトへ切り替え)、ライト中は月(ダークへ切り替え)を表示する
    render() {
        const dark = isDark();
        this.sunTarget.classList.toggle('hidden', !dark);
        this.moonTarget.classList.toggle('hidden', dark);
    }
}
