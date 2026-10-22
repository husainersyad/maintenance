<?php

namespace App\Http\Controllers;

use App\Enums\MenuKey;
use App\Support\HelpGuide;
use App\Support\MenuAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;
use League\CommonMark\Extension\HeadingPermalink\HeadingPermalinkExtension;
use League\CommonMark\Normalizer\SlugNormalizer;

class HelpController extends Controller
{
    /**
     * In-app user guide, rendered from docs/TUTORIAL.md (the single source
     * of truth, see CLAUDE.md) and filtered to what the signed-in user can
     * actually reach - see HelpGuide for the markers used in that file.
     */
    public function index(Request $request): View
    {
        $user = $request->user();

        $markdown = HelpGuide::filter(
            file_get_contents(base_path('docs/TUTORIAL.md')),
            HelpGuide::keysFor($user),
        );

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

        $menus = collect(MenuAccess::visibleMenus($user))
            ->reject(fn ($menu) => $menu->value === 'help')
            ->groupBy(fn ($menu) => $menu->group())
            ->map(fn ($group) => $group->map(fn ($menu) => $menu->label())->all());

        $editable = collect(MenuKey::cases())
            ->filter(fn (MenuKey $key) => $user->canEditMenu($key))
            ->map(fn (MenuKey $key) => $key->label())
            ->values();

        return view('help.index', [
            'html' => $html,
            'toc' => $toc,
            'roles' => $user->getRoleNames(),
            'menus' => $menus,
            'editable' => $editable,
        ]);
    }
}
