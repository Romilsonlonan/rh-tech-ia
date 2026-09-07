<?php

namespace App\AI\Agents;

class TrainingAgent extends BaseAgent
{
    protected function getDefaultConfig(): array
    {
        return [
            'difficulty' => 'intermediate',
            'learning_style' => 'visual',
        ];
    }

    public function getName(): string
    {
        return 'training_agent';
    }

    public function getDescription(): string
    {
        return 'Agente de treinamento que cria planos de estudo personalizados e avalia desempenho.';
    }

    protected function requiredContextKeys(): array
    {
        return ['employee_id', 'goals'];
    }

    protected function executeInternal(array $context): AgentResponse
    {
        $employeeId = $context['employee_id'];
        $goals = $context['goals'];
        
        $trainingPlan = $this->generateTrainingPlan($employeeId, $goals);
        $recommendedCourses = $this->suggestCourses($goals);
        
        return AgentResponse::success('Plano de treinamento gerado', [
            'training_plan' => $trainingPlan,
            'recommended_courses' => $recommendedCourses,
        ]);
    }

    protected function generateTrainingPlan(string $employeeId, array $goals): array
    {
        $prompt = "Crie um plano de treinamento detalhado para o colaborador {$employeeId} com os seguintes objetivos: " . implode(', ', $goals);
        
        return [
            'plan' => $this->llm->generate($prompt),
            'duration_weeks' => 12,
            'milestones' => ['Semana 4', 'Semana 8', 'Semana 12'],
        ];
    }

    protected function suggestCourses(array $goals): array
    {
        $goalsText = implode(', ', $goals);
        $contextDocs = $this->rag->query("Cursos para: {$goalsText}");
        
        $prompt = "Com base nestes cursos disponíveis:\n\n{$contextDocs}\n\nSugira os 3 melhores cursos para os objetivos: {$goalsText}";
        
        return [
            ['title' => 'Curso 1', 'match_score' => 0.95],
            ['title' => 'Curso 2', 'match_score' => 0.87],
            ['title' => 'Curso 3', 'match_score' => 0.82],
        ];
    }
}
