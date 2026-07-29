import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    connect() {
        this.update();
    }

    update() {
        const lieuSelect = this.element.querySelector('select[name$="[lieu]"]');
        const rueInput = document.getElementById('lieu-rue');
        const cpInput = document.getElementById('lieu-code-postal');
        const latLngInput = document.getElementById('lieu-lat-lng');

        if (!lieuSelect || !rueInput || !cpInput || !latLngInput) return;

        const selectedOption = lieuSelect.options[lieuSelect.selectedIndex];

        if (selectedOption && selectedOption.value) {
            rueInput.value = selectedOption.getAttribute('data-rue') || '';
            cpInput.value = selectedOption.getAttribute('data-code-postal') || '';

            const lat = selectedOption.getAttribute('data-latitude') || '';
            const lng = selectedOption.getAttribute('data-longitude') || '';

            latLngInput.value = (lat && lng) ? `${lat} / ${lng}` : '';
        } else {
            rueInput.value = '';
            cpInput.value = '';
            latLngInput.value = '';
        }
    }
}
