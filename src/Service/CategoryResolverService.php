<?php

namespace App\Service;

use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class CategoryResolverService
{
    public function __construct(
        private EntityManagerInterface $em,
        private SluggerInterface $slugger
    ) {}

    public function resolveCategory(string $name, ?Category $parent = null): Category
    {
        $slug = strtolower($this->slugger->slug($name));

        $existing = $this->em
            ->getRepository(Category::class)
            ->findOneBy(['slug' => $slug]);

        if ($existing) {
            return $existing;
        }

        $category = new Category();
        $category->setName($name);
        $category->setSlug($slug);
        $category->setParent($parent);
        $category->setIconName('default');

        $this->em->persist($category);

        return $category;
    }
}
