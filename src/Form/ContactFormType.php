<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ContactFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom complet',
                'required' => true,
                'attr' => [
                    'autocomplete' => 'name'
                ],
            ])
            ->add('email', TextType::class, [
                'label' => 'Adresse email',
                'required' => true,
                'attr' => [
                    'autocomplete' => 'email'
                ],
            ])
            ->add('subject', ChoiceType::class, [
                'label' => 'Sujet',
                'required' => true,
                'choices'  => [
                    'Question générale' => 'question',
                    'Support technique' => 'support',
                    'Feedback sur le site' => 'Feedback',
                    'Proposition de collaboration' => 'proposition',
                    'Demande pressse' => 'presse',
                    'Autre' => 'other',
                ],
                'placeholder' => 'Choisissez un sujet',
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Message',
                'required' => true,
                'attr' => [
                    'placeholder' => 'Décrivez votre demande en détail...',
                    'class' => 'auto-height',
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => '<i class="fa-regular fa-paper-plane"></i> Envoyer le message',
                'label_html' => true,

            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
