<?php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class CompanyFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('param', TextType::class, [
                'label' => 'Renseignez le nom de l\'entreprise recherchée'
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Valider'
            ]);
    }
}
