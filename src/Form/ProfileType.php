<?php

namespace App\Form;

use App\Entity\Campus;
use App\Entity\Participant;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Constraints\PasswordStrength;

class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Si le formulaire est utilisé par un Admin, on ajoute le choix du Campus et du statut Actif
        if ($options['is_admin']) {
            $builder
                ->add('campus', EntityType::class, [
                    'class' => Campus::class,
                    'choice_label' => 'nom',
                    'label' => 'Campus',
                    'placeholder' => 'Sélectionner un campus',
                ]);
        }

        $builder
            ->add('email', EmailType::class, [
                'label' => 'Adresse e-mail',
            ])
            ->add('nom', TextType::class, [
                'label' => 'Nom',
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
            ])
            ->add('pseudo', TextType::class, [
                'label' => 'Pseudo',
            ])
            ->add('telephone', TelType::class, [
                'label' => 'Téléphone',
                'required' => false,
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'trim' => true, // <-- Évite les espaces parasites accidentels
                'mapped' => false,
                'required' => false,
                'first_options' => [
                    'label' => 'Mot de passe',
                ],
                'second_options' => [
                    'label' => 'Confirmation',
                ],
                'invalid_message' => 'Les mots de passe ne correspondent pas.',
                'constraints' => [
                    new Length([
                        'min' => 8,
                        'minMessage' => 'Le mot de passe doit contenir au moins {{ limit }} caractères.',
                    ]),
                    //TODO: décommenter après les tests
                    /*new PasswordStrength([
                        'minScore' => PasswordStrength::STRENGTH_MEDIUM,
                        'message' => 'Le mot de passe est trop simple. Utilisez une combinaison de majuscules, minuscules, chiffres et caractères spéciaux.',

                    ]),
                    */
                ],
            ])
            ->add('photoFile', FileType::class, [
                'label' => 'Ma photo',

                // Le champ n'est pas lié à une propriété de l'entité.
                // Le traitement de l'upload est effectué dans le contrôleur.
                'mapped' => false,

                'required' => false,

                // Le champ n'étant pas lié à une propriété de l'entité,
                // ses contraintes de validation sont définies directement dans le formulaire.
                'constraints' => [
                    new Image([
                        'maxSize'=> '2M',
                        'maxSizeMessage' => 'La taille maximale autorisée est de 2 Mo.',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                        ],
                        'mimeTypesMessage' => 'Merci d\'importer une image valide (JPEG ou PNG).',
                    ])
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Participant::class,
            'is_admin' => false,
            'is_new' => false,
            'is_self' => false,
        ]);
    }
}
