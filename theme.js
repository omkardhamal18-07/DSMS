// theme.js - DSMS Theme Engine

const ThemeEngine = {
    STORAGE_KEY: 'dsms_theme_preference',
    
    init() {
        const storedTheme = localStorage.getItem(this.STORAGE_KEY) || 'system';
        this.applyTheme(storedTheme);
        
        // Listen for system theme changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
            if (localStorage.getItem(this.STORAGE_KEY) === 'system') {
                this.applyTheme('system');
            }
        });
    },
    
    setTheme(theme) {
        localStorage.setItem(this.STORAGE_KEY, theme);
        this.applyTheme(theme);
        
        // Emit custom event for other components (like Chart.js) to re-render
        window.dispatchEvent(new CustomEvent('dsmsThemeChanged', { detail: { theme } }));
    },
    
    applyTheme(theme) {
        let isDark = false;
        
        if (theme === 'dark') {
            isDark = true;
        } else if (theme === 'light') {
            isDark = false;
        } else {
            // System Default
            isDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        }
        
        if (isDark) {
            document.body.classList.add('dark-theme');
        } else {
            document.body.classList.remove('dark-theme');
        }
    },
    
    getCurrentTheme() {
        return localStorage.getItem(this.STORAGE_KEY) || 'system';
    },

    isDarkModeActive() {
        return document.body.classList.contains('dark-theme');
    }
};

// Listen for theme changes to update charts globally
window.addEventListener('dsmsThemeChanged', (e) => {
    if (typeof Chart !== 'undefined' && window.dsmsCharts) {
        const isDark = e.detail.theme === 'dark' || (e.detail.theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
        const textColor = isDark ? '#CBD5E1' : '#858796';
        const gridColor = isDark ? '#334155' : 'rgb(234, 236, 244)';
        const borderColor = isDark ? '#1E293B' : 'rgba(234, 236, 244, 1)';

        Chart.defaults.color = textColor;

        window.dsmsCharts.forEach(chart => {
            if (chart.options.scales) {
                if (chart.options.scales.x && chart.options.scales.x.grid) {
                    chart.options.scales.x.ticks = chart.options.scales.x.ticks || {};
                    chart.options.scales.x.ticks.color = textColor;
                }
                if (chart.options.scales.y && chart.options.scales.y.grid) {
                    chart.options.scales.y.grid.color = gridColor;
                    chart.options.scales.y.ticks = chart.options.scales.y.ticks || {};
                    chart.options.scales.y.ticks.color = textColor;
                }
            }
            if (chart.config.type === 'doughnut' || chart.config.type === 'pie') {
                if (chart.data.datasets && chart.data.datasets[0]) {
                    chart.data.datasets[0].hoverBorderColor = borderColor;
                    chart.data.datasets[0].borderColor = borderColor;
                }
            }
            chart.update();
        });
    }
});

// Initialize immediately to prevent FOUC (Flash of Unstyled Content)
ThemeEngine.init();
