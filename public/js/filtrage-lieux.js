/**
 * Gestion du filtrage des lieux et des détails sans Stimulus
 * Compatible avec la navigation Turbo de Symfony
 */
(function () {
    // 1. Mise à jour des champs Rue / Code Postal / Lat / Lng
    function updateLieuDetails() {
        const selectLieu = document.getElementById('sortie_lieu');
        if (!selectLieu) return;

        const rueInput = document.getElementById('lieu-rue');
        const cpInput = document.getElementById('lieu-code-postal');
        const latLngInput = document.getElementById('lieu-lat-lng');

        if (!rueInput || !cpInput || !latLngInput) return;

        const selectedOption = selectLieu.options[selectLieu.selectedIndex];

        if (selectedOption && selectedOption.value) {
            rueInput.value = selectedOption.dataset.rue || selectedOption.getAttribute('data-rue') || '';
            cpInput.value = selectedOption.dataset.codePostal || selectedOption.getAttribute('data-code-postal') || '';

            const lat = selectedOption.dataset.latitude || selectedOption.getAttribute('data-latitude') || '';
            const lng = selectedOption.dataset.longitude || selectedOption.getAttribute('data-longitude') || '';

            latLngInput.value = (lat && lng) ? `${lat} / ${lng}` : '';
        } else {
            rueInput.value = '';
            cpInput.value = '';
            latLngInput.value = '';
        }
    }

    // 2. Chargement des lieux via l'API quand la ville change
    function handleVilleChange(selectVille) {
        const selectLieu = document.getElementById('sortie_lieu');
        if (!selectLieu) return;

        const idVille = selectVille.value;

        // Réinitialisation
        selectLieu.innerHTML = '<option value="">--- Choisir un lieu ---</option>';
        updateLieuDetails();

        if (!idVille) return;

        fetch(`/api/lieux/${idVille}`)
            .then(response => {
                if (!response.ok) throw new Error('Erreur réseau lors du chargement des lieux');
                return response.json();
            })
            .then(lieux => {
                if (lieux.length === 0) {
                    selectLieu.innerHTML = '<option value="">Aucun lieu pour cette ville</option>';
                    return;
                }

                lieux.forEach(lieu => {
                    const option = document.createElement('option');
                    option.value = lieu.id;
                    option.textContent = lieu.nom;
                    option.dataset.rue = lieu.rue ?? '';
                    option.dataset.codePostal = lieu.codePostal ?? '';
                    option.dataset.latitude = lieu.latitude ?? '';
                    option.dataset.longitude = lieu.longitude ?? '';
                    selectLieu.appendChild(option);
                });
            })
            .catch(error => console.error('Erreur filtrage lieux :', error));
    }

    // 3. Écoute globale sur le document (pour résister aux changements de page Turbo)
    document.addEventListener('change', function (event) {
        const target = event.target;
        if (!target) return;

        if (target.id === 'sortie_ville') {
            handleVilleChange(target);
        }

        if (target.id === 'sortie_lieu') {
            updateLieuDetails();
        }
    });

    // 4. Initialisation pour les chargements initiaux (ex: mode édition ou F5)
    document.addEventListener('DOMContentLoaded', updateLieuDetails);
    document.addEventListener('turbo:load', updateLieuDetails);
})();
