import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['input', 'toggle'];

    connect() {
        this.revealed = false;
        this.hideTimer = null;
    }

    disconnect() {
        clearTimeout(this.hideTimer);
    }

    toggle(event) {
        event.preventDefault();
        this.revealed = !this.revealed;
        this.inputTarget.type = this.revealed ? 'text' : 'password';
        this.toggleTarget.textContent = this.revealed ? 'Hide' : 'Show';
        this.toggleTarget.setAttribute('aria-pressed', this.revealed ? 'true' : 'false');
        this.inputTarget.focus();
    }

    peek() {
        if (this.revealed) {
            return;
        }

        const input = this.inputTarget;
        const caret = input.selectionStart;
        input.type = 'text';
        clearTimeout(this.hideTimer);
        this.hideTimer = setTimeout(() => {
            if (this.revealed) {
                return;
            }

            input.type = 'password';
            try {
                input.setSelectionRange(caret, caret);
            } catch (error) {
                // Some browsers cannot restore the caret after the field type changes.
            }
        }, 700);
    }
}
