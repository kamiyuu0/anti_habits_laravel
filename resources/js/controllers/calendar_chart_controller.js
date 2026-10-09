import { Controller } from '@hotwired/stimulus';
import { isDark } from '../lib/theme';

// ECharts は CSS 変数を解釈できないため、テーマごとの配色をここで持つ
const PALETTES = {
    light: { empty: '#ebedf0', filled: '#216e39', split: '#f0f0f0', text: '#6e7079', tooltipBg: '#ffffff', tooltipText: '#464646' },
    dark: { empty: '#3d4451', filled: '#39d353', split: '#3d4451', text: '#a6adbb', tooltipBg: '#1d232a', tooltipText: '#d1d5db' },
};
// 活動記録ヒートマップ (Rails 版の rails_charts calendar_chart 相当)
export default class extends Controller {
    static values = {
        data: Array,
        start: String,
        end: String,
    };

    async connect() {
        // ECharts はサイズが大きいため、ヒートマップを表示するページでのみ読み込む
        const { default: echarts } = await import('../lib/echarts_calendar');
        if (!this.element.isConnected) return;

        this.chart = echarts.init(this.element);
        this.chart.setOption(this.buildOption());

        this.resize = () => this.chart.resize();
        window.addEventListener('resize', this.resize);

        this.applyTheme = () => this.chart.setOption(this.buildOption());
        document.addEventListener('theme:changed', this.applyTheme);
    }

    buildOption() {
        const palette = isDark() ? PALETTES.dark : PALETTES.light;
        return {
            tooltip: {
                position: 'top',
                formatter: (params) => params.data[0],
                backgroundColor: palette.tooltipBg,
                borderColor: palette.split,
                textStyle: { color: palette.tooltipText },
            },
            visualMap: {
                show: false,
                min: 0,
                max: 1,
                inRange: { color: [palette.empty, palette.filled] },
            },
            calendar: {
                top: 30,
                left: 'center',
                right: 10,
                cellSize: ['auto', 13],
                range: [this.startValue, this.endValue],
                itemStyle: { borderWidth: 3, borderColor: 'transparent' },
                yearLabel: { show: false },
                monthLabel: {
                    nameMap: ['1月', '2月', '3月', '4月', '5月', '6月', '7月', '8月', '9月', '10月', '11月', '12月'],
                    fontSize: 10,
                    color: palette.text,
                },
                dayLabel: {
                    firstDay: 1,
                    nameMap: ['日', '月', '火', '水', '木', '金', '土'],
                    fontSize: 10,
                    color: palette.text,
                },
                splitLine: { show: true, lineStyle: { color: palette.split, width: 1 } },
            },
            series: [{ type: 'heatmap', coordinateSystem: 'calendar', data: this.dataValue }],
        };
    }

    disconnect() {
        window.removeEventListener('resize', this.resize);
        document.removeEventListener('theme:changed', this.applyTheme);
        this.chart?.dispose();
    }
}
