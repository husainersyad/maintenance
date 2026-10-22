<?php

namespace App\Http\Controllers;

use App\Enums\PermissionName;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\Normalizer\SlugNormalizer;

class HelpController extends Controller
{
    /**
     * In-app user guide, rendered straight from docs/TUTORIAL.md so the
     * file stays the single source of truth (see CLAUDE.md). Parts marked
     * hide-in-app are developer-only; admin-only parts are only shown to
     * users who can manage users.
     */
    public function index(Request $request): View
    {
        $markdown = file_get_contents(base_path('docs/TUTORIAL.md'));

        $markdown = preg_replace('/<!-- hide-in-app:start -->.*?<!-- hide-in-app:end -->/s', '', $markdown);

        $isAdmin = $request->user()->can(PermissionName::ManageUsers->value);
        $markdown = $isAdmin
            ? preg_replace('/<!-- admin-only:(start|end) -->\n?/', '', $markdown)
            : preg_replace('/<!-- admin-only:start -->.*?<!-- admin-only:end -->\n?/s', '', $markdown);

        $slugger = new SlugNormalizer;
        $toc = [];
        preg_match_all('/^## (.+)$/m', $markdown, $matches);
        foreach ($matches[1] as $title) {
            $toc[$slugger->normalize($title)] = $title;
        }

        $html = Str::markdown($markdown, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
            'heading_permalink' => ['id_prefix' => '', 'fragment_prefix' => '', 'symbol' => '#', 'html_class' => 'help-anchor'],
        ], [new HeadingPermalinkExtension]);

        return view('help.index', ['html' => $html, 'toc' => $toc]);
    }
}
