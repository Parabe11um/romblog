<?php

use App\Http\Controllers\PostController;
use App\Http\Controllers\SiteController;
use App\Http\Controllers\ProfileController;
use App\Models\Category;
use App\Models\Post;
use Illuminate\Support\Facades\Route;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

Route::get('/sitemap.xml', function () {
    // Создаём объект Sitemap
    $sitemap = Sitemap::create();

    // Добавляем статические страницы
    $sitemap->add(Url::create('/'))
        ->add(Url::create('/about-us'));

    // Добавляем URL всех категорий
    Category::all()->each(function (Category $category) use ($sitemap) {
        $sitemap->add(
            Url::create("/category/{$category->slug}")
                ->setLastModificationDate($category->updated_at) // Указываем дату обновления категории
        );
    });

    // Добавляем URL всех постов
    Post::all()->each(function (Post $post) use ($sitemap) {
        $sitemap->add(
            Url::create("/{$post->slug}")
                ->setLastModificationDate($post->updated_at) // Указываем дату обновления поста
        );
    });

    // Возвращаем ответ в формате XML
    return $sitemap->toResponse(request());
});

//Route::get('/dashboard', function () {
//    return view('dashboard');
//})->middleware(['auth', 'verified'])->name('dashboard');
//
//Route::middleware('auth')->group(function () {
//    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
//    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
//    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
//});
//
//require __DIR__.'/auth.php';

Route::get('/', [PostController::class, 'home'])->name('home');
Route::get('/search', [PostController::class, 'search'])->name('search');
Route::get('/about-us', [SiteController::class, 'about'])->name('about-us');
Route::get('/category/{category:slug}', [PostController::class, 'byCategory'])->name('by-category');
Route::get('/{post:slug}', [PostController::class, 'show'])->name('view');
