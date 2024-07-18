<?php
// src/Form/UserProfileType.php

namespace App\Form;

use App\Entity\Club;
use App\Entity\UserProfile;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class UserProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Prénom'])
            ->add('lastname', TextType::class, ['label' => 'Nom de famille'])
            // ->add('club', EntityType::class, [
            //     'class' => Club::class,
            //     'choice_label' => function (Club $club) {
            //         return $club->getName();
            //     },
            //     'label' => 'Club',
            //     'placeholder' => 'Choisir un club',
            //     'required' => false,
            //     'choice_attr' => function (Club $club) {
            //         // Retourne les attributs supplémentaires, y compris le chemin du logo
            //         return [
            //             'data-logo' => $club->getLogoClub(), // Utilisation du chemin du logo
            //         ];
            //     },
            // ])
            ->add('birth_date', DateType::class, [
                'label' => 'Date de naissance',
                'widget' => 'single_text',
            ])
            ->add('save', SubmitType::class, ['label' => 'Mettre à jour le profil']);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserProfile::class,
        ]);
    }
}
