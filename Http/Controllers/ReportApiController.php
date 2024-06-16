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

class ReportApiController extends Controller
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
            $articles[] = (object)['id' => $a->id, 'title' => $a->getAttributeInLocale('title', $locale), 'text' => $a->getAttributeInLocale('text', $locale)];
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

}
