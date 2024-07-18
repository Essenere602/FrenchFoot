<?php
// src/Form/ChampionnatType.php
namespace App\Form;

use App\Entity\Championnat;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChampionnatType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('country', TextType::class, [
                'required' => true,
                'label' => 'Country',
            ])
            ->add('ligue', TextType::class, [
                'required' => true,
                'label' => 'Ligue',
            ])
            ->add('code_api', TextType::class, [
                'required' => true,
                'label' => 'Code API',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Championnat::class,
        ]);
    }
}
