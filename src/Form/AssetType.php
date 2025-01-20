<?php

namespace App\Form;

use App\Entity\Asset;
use App\Entity\user;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AssetType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('label')
            ->add('currency', ChoiceType::class, [
                'choices' => [
                    'BTC' => 'BTC',
                    'ETH' => 'ETH',
                    'IOTA' => 'IOTA',
                ],
                'placeholder' => 'Choose currency',
            ])
            ->add('amount')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Asset::class,
        ]);
    }
}
