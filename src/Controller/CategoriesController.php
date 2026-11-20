<?php

declare(strict_types=1);

namespace App\Controller;

use App\Core\Attribute\Route;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Http\Url;
use App\Core\Http\View;
use App\Middleware\RequireRole;
use App\Middleware\RequireSession;
use App\Middleware\VerifyCsrf;
use App\Repository\CategoryRepositoryInterface;
use App\Repository\ProductRepositoryInterface;
use App\Service\AuthServiceInterface;
use App\Validation\CategoryValidator;
use PDOException;

final class CategoriesController extends WebController
{
    public function __construct(
        private CategoryRepositoryInterface $categories,
        private ProductRepositoryInterface $products,
        private CategoryValidator $validator,
        AuthServiceInterface $auth,
        View $view,
        Url $url,
    ) {
        parent::__construct($auth, $view, $url);
    }

    #[Route('/admin/categories', methods: 'GET', middleware: [RequireSession::class, RequireRole::class], role: 'admin')]
    public function index(Request $request): Response
    {
        if ($denied = $this->requireAdmin()) {
            return $denied;
        }

        return $this->renderAdmin('categories/index', $this->indexData());
    }

    #[Route('/admin/categories', methods: 'POST', middleware: [RequireSession::class, RequireRole::class, VerifyCsrf::class], role: 'admin')]
    public function store(Request $request): Response
    {
        if ($denied = $this->requireAdmin()) {
            return $denied;
        }

        $input = ['name' => trim((string) $request->input('name', ''))];
        $errors = $this->validator->validate($input);
        if ($errors === [] && $this->categories->findByName($input['name']) !== null) {
            $errors['name'] = 'That category already exists.';
        }

        if ($errors !== []) {
            return $this->renderAdmin('categories/index', $this->indexData($input, $errors), 422);
        }

        try {
            $this->categories->create($input['name']);
            $this->flash('Category created.');
        } catch (PDOException) {
            $this->flash('That category could not be created.', 'error');
        }

        return $this->redirect('/admin/categories');
    }

    #[Route('/admin/categories/{id}/update', methods: 'POST', middleware: [RequireSession::class, RequireRole::class, VerifyCsrf::class], role: 'admin')]
    public function update(Request $request): Response
    {
        if ($denied = $this->requireAdmin()) {
            return $denied;
        }

        $category = $this->categories->findById((int) $request->route('id'));
        if ($category === null) {
            $this->flash('That category could not be found.', 'error');

            return $this->redirect('/admin/categories');
        }

        $input = ['name' => trim((string) $request->input('name', ''))];
        $errors = $this->validator->validate($input);
        $existing = $errors === [] ? $this->categories->findByName($input['name']) : null;
        if ($existing !== null && $existing->id !== $category->id) {
            $errors['name'] = 'That category already exists.';
        }

        if ($errors !== []) {
            $this->flash($errors['name'] ?? 'Category could not be updated.', 'error');

            return $this->redirect('/admin/categories');
        }

        $previous = $category->name;
        $category->name = $input['name'];

        try {
            $this->categories->update($category, $previous);
            $this->flash('Category updated.');
        } catch (PDOException) {
            $this->flash('That category could not be updated.', 'error');
        }

        return $this->redirect('/admin/categories');
    }

    #[Route('/admin/categories/{id}/delete', methods: 'POST', middleware: [RequireSession::class, RequireRole::class, VerifyCsrf::class], role: 'admin')]
    public function destroy(Request $request): Response
    {
        if ($denied = $this->requireAdmin()) {
            return $denied;
        }

        $category = $this->categories->findById((int) $request->route('id'));
        if ($category === null) {
            $this->flash('That category could not be found.', 'error');

            return $this->redirect('/admin/categories');
        }

        $using = $this->categories->countProductsUsing($category->name);

        try {
            $slugs = $this->categories->deleteWithProducts((int) $category->id, $category->name);
            foreach ($slugs as $slug) {
                delete_product_image($slug);
            }

            if ($using > 0) {
                $this->flash(
                    'Category deleted with ' . $using . ' product' . ($using === 1 ? '' : 's') . '.',
                );
            } else {
                $this->flash('Category deleted.');
            }
        } catch (\RuntimeException $exception) {
            $this->flash($exception->getMessage(), 'error');
        } catch (PDOException) {
            $this->flash('That category could not be deleted.', 'error');
        }

        return $this->redirect('/admin/categories');
    }

    /**
     * @param array<string, string> $old
     * @param array<string, string> $errors
     * @return array<string, mixed>
     */
    private function indexData(array $old = [], array $errors = []): array
    {
        $categories = $this->categories->all();
        $productCounts = [];
        $productsByCategory = [];

        foreach ($categories as $category) {
            $id = (int) $category->id;
            $products = $this->products->findByCategory($category->name);
            $productCounts[$id] = count($products);
            $productsByCategory[$id] = $products;
        }

        return [
            'title' => 'Categories',
            'activeNav' => 'categories',
            'categories' => $categories,
            'productCounts' => $productCounts,
            'productsByCategory' => $productsByCategory,
            'old' => $old,
            'errors' => $errors,
        ];
    }
}
