<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\BaseApiController;
use App\Services\Notion\StudyExporter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotionController extends BaseApiController
{
    public function __construct(
        protected StudyExporter $studyExporter
    ) {}

    public function studies(): JsonResponse
    {
        try {
            $studies = $this->studyExporter->syncFromNotion();
            return $this->success($studies, 'Estudos sincronizados.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function studyContent(string $pageId): JsonResponse
    {
        try {
            $content = $this->studyExporter->getStudyContent($pageId);
            return $this->success(['content' => $content]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function exportStudy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'required|string',
            'content' => 'required|string',
            'tags' => 'array',
            'tags.*' => 'string',
        ]);

        try {
            $result = $this->studyExporter->exportStudyNote(
                $validated['title'],
                $validated['category'],
                $validated['content'],
                $validated['tags'] ?? []
            );

            return $this->success($result, 'Estudo exportado.', 201);
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function updateStudy(Request $request, string $pageId): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'category' => 'sometimes|string',
            'tags' => 'array',
            'tags.*' => 'string',
        ]);

        try {
            $result = $this->studyExporter->updateStudyNote($pageId, $validated);
            return $this->success($result, 'Estudo atualizado.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function deleteStudy(string $pageId): JsonResponse
    {
        try {
            $this->studyExporter->deleteStudyNote($pageId);
            return $this->success(null, 'Estudo removido.');
        } catch (\Exception $e) {
            return $this->error($e->getMessage(), 500);
        }
    }
}
