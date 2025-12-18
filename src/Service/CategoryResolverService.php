<?php

namespace App\Service;

use App\Entity\Category;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\Slugger\SluggerInterface;

class CategoryResolverService
{
    public function __construct(
        private EntityManagerInterface $em,
        private SluggerInterface $slugger,
    ) {}

    public function resolveCategory(string $categoryName, ?string $subcategoryName = null): Category
    {
        // Parent category
        $category = $this->getOrCreate($categoryName, null);

        // Sub category
        if ($subcategoryName) {
            return $this->getOrCreate($subcategoryName, $category);
        }

        return $category;
    }

    private function getOrCreate(string $name, ?Category $parent): Category
    {
        $slug = strtolower($this->slugger->slug($name));
        $repo = $this->em->getRepository(Category::class);

        $existing = $repo->findOneBy(['slug' => $slug]);
        if ($existing) {
            return $existing;
        }

        $cat = new Category();
        $cat->setName($name);
        $cat->setSlug($slug);
        $cat->setIconName('default');
        $cat->setParent($parent);

        $this->em->persist($cat);

        return $cat;
    }

    public function findBySlug(string $slug): ?Category
    {
        $repo = $this->em->getRepository(Category::class);
        return $repo->findOneBy(['slug' => strtolower($slug)]);
    }
}
