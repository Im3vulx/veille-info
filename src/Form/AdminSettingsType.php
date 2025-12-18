<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\ColorType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AdminSettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // --- Général ---
        $builder
            ->add('siteName', TextType::class, ['label' => 'Nom du site'])
            ->add('siteUrl', UrlType::class, ['label' => 'URL du site'])
            ->add('siteDescription', TextareaType::class, ['label' => 'Description du site', 'attr' => ['rows' => 3]])
            ->add('adminEmail', EmailType::class, ['label' => 'Email administrateur'])
            ->add('timezone', ChoiceType::class, [
                'label' => 'Fuseau horaire',
                'choices' => [
                    'Europe/Paris' => 'Europe/Paris',
                    'Europe/London' => 'Europe/London',
                    'America/New_York' => 'America/New_York',
                    'Asia/Tokyo' => 'Asia/Tokyo',
                ],
            ]);

        // --- Email ---
        $builder
            ->add('smtpHost', TextType::class, ['label' => 'Serveur SMTP'])
            ->add('smtpPort', IntegerType::class, ['label' => 'Port SMTP'])
            ->add('smtpUsername', TextType::class, ['label' => 'Nom d\'utilisateur SMTP', 'required' => false])
            ->add('smtpPassword', TextType::class, ['label' => 'Mot de passe SMTP', 'required' => false]) // Use PasswordType in real app
            ->add('fromEmail', EmailType::class, ['label' => 'Email expéditeur'])
            ->add('fromName', TextType::class, ['label' => 'Nom expéditeur']);

        // --- Sécurité ---
        $builder
            ->add('enableRegistration', CheckboxType::class, ['label' => 'Autoriser les inscriptions', 'required' => false])
            ->add('requireEmailVerification', CheckboxType::class, ['label' => 'Vérification email obligatoire', 'required' => false])
            ->add('enableTwoFactor', CheckboxType::class, ['label' => 'Authentification à deux facteurs', 'required' => false])
            ->add('sessionTimeout', IntegerType::class, ['label' => 'Durée de session (heures)'])
            ->add('maxLoginAttempts', IntegerType::class, ['label' => 'Tentatives de connexion max']);

        // --- Contenu ---
        $builder
            ->add('enableComments', CheckboxType::class, ['label' => 'Activer les commentaires', 'required' => false])
            ->add('moderateComments', CheckboxType::class, ['label' => 'Modération des commentaires', 'required' => false])
            ->add('enableBookmarks', CheckboxType::class, ['label' => 'Système de favoris', 'required' => false])
            ->add('enableNewsletter', CheckboxType::class, ['label' => 'Newsletter', 'required' => false])
            ->add('articlesPerPage', ChoiceType::class, [
                'label' => 'Articles par page',
                'choices' => [
                    '5 articles' => 5,
                    '10 articles' => 10,
                    '15 articles' => 15,
                    '20 articles' => 20,
                    '25 articles' => 25,
                ]
            ]);

        // --- Apparence ---
        $builder
            ->add('primaryColor', ColorType::class, ['label' => 'Couleur primaire'])
            ->add('secondaryColor', ColorType::class, ['label' => 'Couleur secondaire'])
            ->add('accentColor', ColorType::class, ['label' => 'Couleur d\'accent'])
            ->add('enableDarkMode', CheckboxType::class, ['label' => 'Activer le mode sombre', 'required' => false])
            ->add('defaultTheme', ChoiceType::class, [
                'label' => 'Thème par défaut',
                'choices' => [
                    'Clair' => 'light',
                    'Sombre' => 'dark',
                    'Système' => 'system',
                ]
            ]);

        // --- Notifications ---
        $builder
            ->add('emailNotifications', CheckboxType::class, ['label' => 'Notifications par email', 'required' => false])
            ->add('newUserNotifications', CheckboxType::class, ['label' => 'Nouveaux utilisateurs', 'required' => false])
            ->add('newCommentNotifications', CheckboxType::class, ['label' => 'Nouveaux commentaires', 'required' => false])
            ->add('systemAlerts', CheckboxType::class, ['label' => 'Alertes système', 'required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // 'data_class' => SettingsEntity::class, // Décommentez si vous liez à une entité
        ]);
    }
}
