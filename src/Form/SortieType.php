<?php

namespace App\Form;

use App\Entity\Campus;
use App\Entity\Lieu;
use App\Entity\Sortie;
use App\Entity\Ville;
use App\Repository\LieuRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SortieType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom de la sortie',
            ])
            ->add('dateHeureDebut', DateTimeType::class, [
                'label' => 'Date et heure de la sortie',
                'widget' => 'single_text',
            ])
            ->add('duree', IntegerType::class, [
                'label' => 'Durée (en minutes)',
            ])
            ->add('dateLimiteInscription', DateTimeType::class, [
                'label' => 'Date limite d\'inscription',
                'widget' => 'single_text',
            ])
            ->add('nbInscriptionsMax', IntegerType::class, [
                'label' => 'Nombre de places',
            ])
            ->add('siteOrganisateur', EntityType::class, [
                'class' => Campus::class,
                'choice_label' => 'nom',
                'label' => 'Campus',
                'placeholder' => '--- Choisir un campus ---',
                'disabled' => true, // 💡 Grise le champ et bloque la modification côté serveur
            ])

            // --- NOUVEAU CHAMP : Ville (pilote le filtrage) @author Développeur JS/UX

            ->add('ville', EntityType::class, [
                'class' => Ville::class,
                'choice_label' => 'nom',
                'placeholder' => '--- Choisir une ville ---',
                'label' => 'Ville',
                'mapped' => false,
                'required' => false,
                'attr' => ['id' => 'sortie_ville'],
            ])
            /** ->add('lieu', EntityType::class, [
             * 'class' => Lieu::class,
             * 'choice_label' => 'nom',
             * 'placeholder' => '--- Choisir un lieu ---',
             * 'label' => 'Lieu',
             * // 💡 On délègue la requête au LieuRepository
             * 'query_builder' => function (LieuRepository $lieuRepository) {
             * return $lieuRepository->createFindAllWithVilleQueryBuilder();
             * },
             * 'choice_attr' => function (Lieu $lieu) {
             * return [
             * 'data-rue' => $lieu->getRue(),
             * 'data-code-postal' => $lieu->getVille()?->getCodePostal() ?? '',
             * 'data-latitude' => $lieu->getLatitude(),
             * 'data-longitude' => $lieu->getLongitude(),
             * ];
             * },
             * ])*/
            ->add('infoSortie', TextareaType::class, [
                'label' => 'Description et infos',
                'required' => false,
                'attr' => ['rows' => 4],
            ])
            ->add('enregistrer', SubmitType::class, [
                'label' => 'Enregistrer en brouillon',
                'attr' => ['class' => 'btn btn-outline-secondary w-100']
            ])
            ->add('publier', SubmitType::class, [
                'label' => 'Publier la sortie',
                'attr' => ['class' => 'btn btn-primary w-100']
            ]);
        // Ajoute le champ "lieu" (vide au départ) et les écouteurs @author Développeur JS/UX
        $this->ajouterChampLieu($builder, null);
        $this->ajouterEcouteursVille($builder);

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Sortie::class,
        ]);
    }


    /**
     * Enregistre les écouteurs PRE_SET_DATA et PRE_SUBMIT chargés
     * de reconstruire le champ "lieu" selon la ville concernée.
     *
     * @param FormBuilderInterface $builder
     * @return void
     */
    private function ajouterEcouteursVille(FormBuilderInterface $builder): void
    {
        // 1. PRE_SET_DATA : Filtre les choix du champ "lieu" selon la ville associée
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $sortie = $event->getData();
            $form = $event->getForm();

            $ville = ($sortie instanceof Sortie && $sortie->getLieu() !== null)
                ? $sortie->getLieu()->getVille()
                : null;

            // Reconstitution du champ "lieu" filtré sur cette ville
            $this->ajouterChampLieu($form, $ville?->getId());
        });

        // 2. POST_SET_DATA : Assigne la valeur au champ non mappé "ville" APRÈS la passe de Symfony
        $builder->addEventListener(FormEvents::POST_SET_DATA, function (FormEvent $event) {
            $sortie = $event->getData();
            $form = $event->getForm();

            if ($sortie instanceof Sortie && $sortie->getLieu() !== null) {
                $ville = $sortie->getLieu()->getVille();
                if ($ville !== null && $form->has('ville')) {
                    $form->get('ville')->setData($ville);
                }
            }
        });

        // 3. PRE_SUBMIT : Reconstruit le champ "lieu" avec la ville soumise
        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) {
            $data = $event->getData();
            $idVille = !empty($data['ville']) ? (int)$data['ville'] : null;

            $this->ajouterChampLieu($event->getForm(), $idVille);
        });
    }


    /**
     * (Re)déclare le champ "lieu", filtré sur la ville donnée
     * si elle est fournie, sinon avec une liste vide.
     *
     * Conserve le "choice_attr" existant (data-rue, data-code-postal,
     * data-latitude, data-longitude) utilisé pour le remplissage
     * automatique des champs Rue / Code postal / Latitude / Longitude.
     *
     * @param FormBuilderInterface|FormInterface $form
     * @param int|null $idVille
     * @return void
     * @author Développeur JS/UX
     */
    private function ajouterChampLieu($form, ?int $idVille): void
    {
        $form->add('lieu', EntityType::class, [
            'class' => Lieu::class,
            'choice_label' => 'nom',
            'placeholder' => '--- Choisir un lieu ---',
            'label' => 'Lieu',
            'attr' => ['id' => 'sortie_lieu'],
            'query_builder' => function (LieuRepository $lieuRepository) use ($idVille) {
                if ($idVille === null) {
                    // Aucune ville choisie -> liste vide
                    return $lieuRepository->createFindAllWithVilleQueryBuilder()
                        ->andWhere('1 = 0');
                }

                return $lieuRepository->createFindByVilleQueryBuilder($idVille);
            },
            'choice_attr' => function (Lieu $lieu) {
                return [
                    'data-rue' => $lieu->getRue(),
                    'data-code-postal' => $lieu->getVille()?->getCodePostal() ?? '',
                    'data-latitude' => $lieu->getLatitude(),
                    'data-longitude' => $lieu->getLongitude(),
                ];
            },
        ]);

    }
}
