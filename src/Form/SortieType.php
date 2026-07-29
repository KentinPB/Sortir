<?php

namespace App\Form;

use App\Entity\Campus;
use App\Entity\Lieu;
use App\Entity\Sortie;
use App\Repository\LieuRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
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
                'choice_label' => 'nom', // Affiche le nom du campus
                'label' => 'Campus',
                'placeholder' => '--- Choisir un campus ---',
            ])
            ->add('lieu', EntityType::class, [
                'class' => Lieu::class,
                'choice_label' => 'nom',
                'placeholder' => '--- Choisir un lieu ---',
                'label' => 'Lieu',
                // 💡 On délègue la requête au LieuRepository
                'query_builder' => function (LieuRepository $lieuRepository) {
                    return $lieuRepository->createFindAllWithVilleQueryBuilder();
                },
                'choice_attr' => function (Lieu $lieu) {
                    return [
                        'data-rue' => $lieu->getRue(),
                        'data-code-postal' => $lieu->getVille()?->getCodePostal() ?? '',
                        'data-latitude' => $lieu->getLatitude(),
                        'data-longitude' => $lieu->getLongitude(),
                    ];
                },
            ])
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
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Sortie::class,
        ]);
    }
}
