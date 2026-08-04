/**
 * Filtrage dynamique des lieux en fonction de la ville
 * sélectionnée dans le formulaire de sortie, et remplissage
 * automatique de Rue / Code postal / Latitude / Longitude.
 * @author Développeur JS/UX
 */
document.addEventListener('DOMContentLoaded', () => {
    const selectVille = document.getElementById('sortie_ville');
    const selectLieu = document.getElementById('sortie_lieu');

    if (!selectVille || !selectLieu) {
        return;
    }

    selectVille.addEventListener('change', () => {
        const idVille = selectVille.value;

        selectLieu.innerHTML = '';

        if (!idVille) {
            const option = document.createElement('option');
            option.textContent = "--- Choisir un lieu ---";
            selectLieu.appendChild(option);
            return;
        }

        fetch(`/api/lieux/${idVille}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur réseau lors de la récupération des lieux');
                }
                return response.json();
            })
            .then(lieux => {
                const optionVide = document.createElement('option');
                optionVide.textContent = '--- Choisir un lieu ---';
                selectLieu.appendChild(optionVide);

                if (lieux.length === 0) {
                    const option = document.createElement('option');
                    option.textContent = 'Aucun lieu pour cette ville';
                    selectLieu.appendChild(option);
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
    });
});
