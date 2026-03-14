<?php

declare(strict_types=1);

namespace App\Adapters\In\Http\Controllers\Note;

use App\Adapters\In\Http\Presenters\JsonPresenter;
use App\Adapters\In\Http\Requests\Note\CreateNoteRequest;
use App\Application\Note\UseCases\CreateNoteHandler;
use Illuminate\Http\JsonResponse;

final class CreateNoteController
{
    public function __invoke(
        CreateNoteRequest $request,
        CreateNoteHandler $useCase,
        JsonPresenter $presenter,
    ): JsonResponse {
        $data = $request->validated();

        $result = $useCase->handle(
            (string) $data['customer_name'],
            (string) $data['transaction_date'],
        );

        if ($result->isFailure()) {
            return $presenter->failure($result);
        }

        return $presenter->success($result);
    }
}
