<?php

namespace App\Form;

use App\Entity\Article;
use App\Entity\Category;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ArticleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class, [
                'label' => 'Titre de l\'article',
                'attr' => ['placeholder' => 'Entrez le titre ici...']
            ])
            ->add('summary', TextareaType::class, [
                'label' => 'Résumé (chapô)',
                'attr' => ['rows' => 3, 'placeholder' => 'Une courte introduction...']
            ])
            ->add('content', TextareaType::class, [
                'label' => 'Contenu complet',
                'attr' => ['rows' => 10, 'class' => 'auto-expand']
            ])
            ->add('imageUrl', UrlType::class, [
                'label' => 'URL de l\'image de couverture',
                'required' => false,
                'attr' => ['placeholder' => 'https://...']
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'label' => 'Catégorie',
                'placeholder' => 'Choisir une catégorie',
                'attr' => ['class' => 'form-select']
            ])
            ->add('published', CheckboxType::class, [
                'label' => 'Publier cet article immédiatement ?',
                'required' => false,
                'label_attr' => ['class' => 'checkbox-switch-label']
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Article::class,
        ]);
    }
}
