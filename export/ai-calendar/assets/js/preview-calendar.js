class PreviewCalendar {
    constructor(container, options = {}) {
        this.$container = jQuery(container);
        this.options = {
            onDayClick: null,
            ...options
        };
        
        this.currentDate = new Date();
        this.activeDay = null;
        
        this.init();
    }
    
    init() {
        this.renderCalendar();
        this.attachEventListeners();
    }
    
    renderCalendar() {
        const year = this.currentDate.getFullYear();
        const month = this.currentDate.getMonth();
        
        // Get first day of month and total days
        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        const totalDays = lastDay.getDate();
        const firstDayIndex = firstDay.getDay();
        
        // Generate calendar HTML
        let html = `
            <div class="preview-calendar">
                <div class="calendar-header">
                    <button type="button" class="prev-month">&larr;</button>
                    <div class="current-month">${this.formatMonth(month)} ${year}</div>
                    <button type="button" class="next-month">&rarr;</button>
                </div>
                <div class="weekdays">
                    ${this.getWeekDays().map(day => `<div class="weekday">${day}</div>`).join('')}
                </div>
                <div class="days">`;
        
        // Add empty cells for days before first day of month
        for (let i = 0; i < firstDayIndex; i++) {
            html += '<div class="day empty"></div>';
        }
        
        // Add days of month
        const today = new Date();
        for (let day = 1; day <= totalDays; day++) {
            const date = new Date(year, month, day);
            const isToday = this.isToday(date);
            const isActive = this.activeDay && this.isSameDay(date, this.activeDay);
            
            html += `
                <div class="day${isToday ? ' today' : ''}${isActive ? ' active' : ''}" 
                     data-date="${this.formatDate(date)}">
                    <span class="day-number">${day}</span>
                </div>`;
        }
        
        html += '</div></div>';
        
        // Preserve existing styles
        const existingStyles = {};
        if (this.$container.length) {
            const style = this.$container[0].style;
            for (let i = 0; i < style.length; i++) {
                const prop = style[i];
                existingStyles[prop] = style.getPropertyValue(prop);
            }
        }
        
        // Update container content
        this.$container.html(html);
        
        // Reapply styles
        if (this.$container.length) {
            Object.entries(existingStyles).forEach(([prop, value]) => {
                this.$container[0].style.setProperty(prop, value);
            });
        }
    }
    
    attachEventListeners() {
        this.$container.on('click', '.day:not(.empty)', (e) => {
            const $day = jQuery(e.currentTarget);
            const date = $day.data('date');
            
            // Update active state
            this.$container.find('.day').removeClass('active');
            $day.addClass('active');
            
            this.activeDay = new Date(date);
            
            // Trigger callback
            if (this.options.onDayClick) {
                this.options.onDayClick(date);
            }
        });
        
        this.$container.on('click', '.prev-month', () => {
            this.currentDate.setMonth(this.currentDate.getMonth() - 1);
            this.renderCalendar();
        });
        
        this.$container.on('click', '.next-month', () => {
            this.currentDate.setMonth(this.currentDate.getMonth() + 1);
            this.renderCalendar();
        });
    }
    
    getWeekDays() {
        return ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
    }
    
    formatMonth(month) {
        const months = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];
        return months[month];
    }
    
    formatDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }
    
    isToday(date) {
        const today = new Date();
        return this.isSameDay(date, today);
    }
    
    isSameDay(date1, date2) {
        return date1.getFullYear() === date2.getFullYear() &&
               date1.getMonth() === date2.getMonth() &&
               date1.getDate() === date2.getDate();
    }
    
    getActiveDay() {
        return this.activeDay ? this.formatDate(this.activeDay) : null;
    }
    
    clickDay(date) {
        const $day = this.$container.find(`.day[data-date="${date}"]`);
        if ($day.length) {
            $day.trigger('click');
        }
    }
    
    refresh() {
        this.renderCalendar();
    }
}

// Make PreviewCalendar available globally
window.PreviewCalendar = PreviewCalendar; 