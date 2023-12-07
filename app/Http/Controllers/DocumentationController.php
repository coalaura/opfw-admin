<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DocumentationController extends Controller
{
    const Documentations = [
        'damage_modifier'    => 'DamageModifier',
        'disconnect_reasons' => 'DisconnectReasons',
        'markdown'           => 'Markdown',
    ];

    /**
     * Random documentations.
     *
     * @param Request $request
     * @return Response
     */
    public function docs(Request $request, string $type): Response
    {
        $page = self::Documentations[$type];

        if (empty($page)) {
            abort(404);
        }

        return Inertia::render('Documentation/' . $page);
    }

}
