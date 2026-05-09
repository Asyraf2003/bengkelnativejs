<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Note;

use App\Adapters\In\Http\Controllers\Note\Support\NoteRouteAreaResolver;
use App\Adapters\In\Http\Requests\Note\StoreNoteRevisionRequest;
use App\Application\Note\UseCases\CreateNoteRevisionHandler;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;

final class StoreNoteRevisionController extends Controller
{
    public function __invoke(
        string $noteId,
        StoreNoteRevisionRequest $request,
        CreateNoteRevisionHandler $handler,
        NoteRouteAreaResolver $routes,
    ): RedirectResponse {
        $user = $request->user();
        $actorId = $user !== null ? (string) $user->getAuthIdentifier() : null;

        $enforceWorkspaceEditability = ! $request->routeIs('admin.notes.workspace.update');

        $result = $handler->handle(
            $noteId,
            $request->validated(),
            $actorId,
            $enforceWorkspaceEditability,
        );

        if ($result->isFailure()) {
            return back()
                ->withErrors(['revision' => $result->message() ?? 'Revisi nota gagal disimpan.'])
                ->withInput();
        }

        return redirect()
            ->route($routes->showRoute($request), ['noteId' => $noteId])
            ->with('success', $result->message() ?? 'Revisi nota berhasil disimpan.');
    }
}
