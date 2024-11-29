<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Entity\Category;
use Symfony\Component\Validator\Constraints as Asserts;


class PostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('title', TextType::class, [
            'label' => 'Titre',
            //new Asserts\NotBlank(),
        ])
        ->add('content', TextareaType::class, [
            'label' => 'Contenu',
        ])
        ->add('publishedAt', DateTimeType::class, [
            'label' => 'Date de publication',
            'widget' => 'single_text',
            'required' => false,
        ])
        ->add('picture', TextType::class, [
            'label' => 'URL de l’image',
            'required' => false,
        ])
        ->add('category', EntityType::class, [
            'class' => Category::class, // Utilise l'entité Category
            'choice_label' => 'name', // Affiche le nom des catégories
            'placeholder' => 'Sélectionnez une catégorie', // Optionnel
            'label' => 'Catégorie',
        ]);
    }
}
