<?php

namespace App\Form;

use App\Entity\User;
use App\Entity\UserBanned;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class UserBannedType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('bannedDate', null, [
                'widget' => 'single_text',
            ])
            ->add('numberBan')
            ->add('isPermanentlyBanned')
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'id',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => UserBanned::class,
        ]);
    }
}
