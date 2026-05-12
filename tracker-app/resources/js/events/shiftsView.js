export default function shiftView() {
    return {
        view: 'cards',
        init() {
            const saved = localStorage.getItem('upcomingShiftsView');
            if (saved) {
                this.view = saved;
            } else {
                // Default: cards on mobile, table on desktop
                this.view = window.innerWidth < 992 ? 'cards' : 'table';
            }
        },
        toggleView() {
            this.view = this.view === 'cards' ? 'table' : 'cards';
            localStorage.setItem('upcomingShiftsView', this.view);
        }
    }
}