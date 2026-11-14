<?php

namespace App\Form;

use App\Entity\AirHost;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class AirHostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, ['label' => 'Name'])
            ->add('role', TextType::class, ['label' => 'Role'])
            ->add('showName', TextType::class, ['label' => 'Show'])
            ->add('initials', TextType::class, [
                'label' => 'Initials',
                'attr' => ['maxlength' => 4],
            ])
            ->add('accent', ColorType::class, ['label' => 'Color'])
            ->add('bio', TextareaType::class, [
                'label' => 'Bio',
                'attr' => ['rows' => 5],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AirHost::class,
        ]);
    }
}
