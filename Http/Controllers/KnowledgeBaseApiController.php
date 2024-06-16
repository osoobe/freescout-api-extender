<?php

namespace Modules\ApiExtender\Http\Controllers;

use App\Conversation;
use App\Mailbox;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Response;
use Illuminate\Http\Request;
use Modules\KnowledgeBase\Entities\KbCategory;
use Modules\KnowledgeBase\Entities\KbArticle;
use Modules\KnowledgeBase\Entities\KbArticleKbCategory;

class KnowledgeBaseApiController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    public function get(Request $request, $mailboxId)
    {
        $mailbox = Mailbox::findOrFail($mailboxId);
        if ($mailbox === null) {
            return Response::json([], 404);
        }
        $categories = \KbCategory::getTree($mailbox->id, [], 0, true);

        $locale = $request->input('locale') ?? \Kb::defaultLocale($mailbox);

        $items = [];

        foreach ($categories as $c) {
            if (!$c->checkVisibility()) {
                continue;
            }
            $items[] = (object)[
                'id' => $c->id,
                'name' => $c->getAttributeInLocale('name', $locale),
                'description' => $c->getAttributeInLocale('description', $locale)
            ];

        }

        return Response::json([
            'mailbox_id' => $mailbox->id,
            'name' => $mailbox->name,
            'categories' => $items,
        ], 200);

    }

    public function category(Request $request, $mailboxId, $categoryId)
    {
        $mailbox = Mailbox::findOrFail($mailboxId);
        if ($mailbox === null) {
            return Response::json([], 404);
        }

        $category = KbCategory::findOrFail($categoryId);
        if (!$category->checkVisibility()) {
            $category = null;
        }
        if ($category === null) {
            return Response::json([], 404);
        }
        $articles = [];
        if ($category) {
            $sortedArticles = $category->getArticlesSorted(true);
        }

        $locale = $request->input('locale') ?? \Kb::defaultLocale($mailbox);

        foreach ($sortedArticles as $a) {
            $a->setLocale($locale);
            $articles[] = (object)[
                'id' => $a->id,
                'title' => $a->getAttributeInLocale('title', $locale),
                'text' => $a->getAttributeInLocale('text', $locale),
                'slug' => $a->getAttributeInLocale('slug', $locale)
            ];
        }

        return Response::json([
            'id' => 0,
            'mailbox_id' => $mailbox->id,
            'name' => $mailbox->name,
            'category' => (object)[
                'id'=>$category->id,
                'name'=>$category->getAttributeInLocale('name', $locale),
                'description'=>$category->getAttributeInLocale('description', $locale),
            ],
            'articles' => $articles,
        ], 200);
    }


    // todo: mailbox should be active.
    public function processMailboxId($mailbox_id)
    {
        try {
            $mailbox_id = \Kb::decodeMailboxId($mailbox_id);

            if ($mailbox_id) {
                $mailbox = Mailbox::findOrFail($mailbox_id);
            }
        } catch (\Exception $e) {
            return null;
        }

        if (empty($mailbox)) {
            return null;
        }

        return $mailbox;
    }
    

    /**
     * Frontend article.
     */
    public function getFrontendArticle(Request $request, $mailbox_id, $article_id, $slug = '', $kb_locale = '')
    {
        $mailbox = $this->processMailboxId($mailbox_id);

        if (!$mailbox) {
            abort(404);
        }

        $locale = $request->input('locale') ?? \Kb::defaultLocale($mailbox);
        $category = null;
        $categories = [];
        $related_articles = [];

        $article = KbArticle::findOrFail($request->article_id);
        

        if (!$article->isPublished()) {
            $article = null;
        } else {
            // Make sure that article has no categories or has at least one visible.
            if (!$article->isVisible()) {
                $article = null;
            }
            if ( !empty($article->categories) ) {
                foreach ($article->categories as $category) {
                    if ($category->id && !$category->checkVisibility()) {
                        continue;
                    }
                    $json_categories[] = (object)[
                        'id'=>$category->id,
                        'name'=>$category->getAttributeInLocale('name', $locale),
                        'description'=>$category->getAttributeInLocale('description', $locale),
                    ];
                }
            }
        }

        // // Make sure there is a slug in URL.
        // if ($article && $article->slug && (empty($request->slug) || $article->slug != $request->slug)) {
        //     return Response::json([
        //         "message": "no article found"
        //     ], 422);
        // }

        // if ($category) {
        //     $related_articles = $category->getArticlesSorted(true);
        //     if (count($related_articles) < 2) {
        //         $related_articles = [];
        //     }
        //     foreach ($related_articles as $i => $related_article) {
        //         if ($related_article->id == $request->article_id) {
        //             unset($related_articles[$i]);
        //         }
        //     }
        // }

        
        return Response::json([
            'id' => $article->id,
            'mailbox_id' => $mailbox->id,
            'mailbox_name' => $mailbox->name,
            'title' => $article->getAttributeInLocale('title', $locale),
            'text' => $article->getAttributeInLocale('text', $locale),
            'slug' => $article->getAttributeInLocale('slug', $locale),
            'categories' => $categories
        ], 200);

    }

}
