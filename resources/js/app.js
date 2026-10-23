import themeToggle from './alpine/theme_toggle';
import calendarChart from './alpine/calendar_chart';
import tagify from './alpine/tagify';

// Livewire が同梱する Alpine を使う。Alpine の開始前にコンポーネントを登録する
document.addEventListener('alpine:init', () => {
    window.Alpine.data('themeToggle', themeToggle);
    window.Alpine.data('calendarChart', calendarChart);
    window.Alpine.data('tagify', tagify);
});
