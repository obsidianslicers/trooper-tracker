export default function eventOrganizationAttendance({ canAttend, troopers, handlers }) {
    return {
        canAttend,
        troopers,
        handlers,

        init() {
            this.$watch('canAttend', value => {
                if (!value) {
                    this.troopers = '';
                    this.handlers = '';
                }
            });
        }
    }
}