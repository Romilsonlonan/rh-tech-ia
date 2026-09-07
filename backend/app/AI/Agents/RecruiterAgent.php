<?php

namespace App\AI\Agents;

class RecruiterAgent extends BaseAgent
{
    protected function getDefaultConfig(): array
    {
        return [
            'experience_level' => 'mid',
            'include_soft_skills' => true,
            'min_score' => 0.7,
        ];
    }

    public function getName(): string
    {
        return 'recruiter_agent';
    }

    public function getDescription(): string
    {
        return 'Agente de recrutamento que analisa candidatos e sugere os melhores perfis para vagas.';
    }

    protected function requiredContextKeys(): array
    {
        return ['vacancy_id', 'candidates'];
    }

    protected function executeInternal(array $context): AgentResponse
    {
        $vacancy = $context['vacancy_id'];
        $candidates = $context['candidates'];
        
        $rankedCandidates = $this->rankCandidates($vacancy, $candidates);
        
        return AgentResponse::success('Análise concluída', [
            'ranked_candidates' => $rankedCandidates,
            'top_picks' => array_slice($rankedCandidates, 0, 3),
        ]);
    }

    protected function rankCandidates(mixed $vacancy, array $candidates): array
    {
        $jobDescription = $this->ragQuery("Vaga: {$vacancy}");
        
        $ranked = array_map(function ($candidate) use ($jobDescription) {
            $candidateInfo = json_encode($candidate);
            $prompt = "Compare este candidato com a vaga:\n\nVaga: {$jobDescription}\n\nCandidato: {$candidateInfo}\n\nDê uma nota de 0 a 1 e explique brevemente.";
            
            $analysis = $this->llm->generate($prompt);
            $score = $this->extractScore($analysis);
            
            return [
                'candidate' => $candidate,
                'score' => $score,
                'analysis' => $analysis,
            ];
        }, $candidates);

        usort($ranked, fn($a, $b) => $b['score'] <=> $a['score']);
        
        return $ranked;
    }

    protected function extractScore(string $analysis): float
    {
        preg_match('/[01]\.[0-9]+/', $analysis, $matches);
        return isset($matches[0]) ? (float) $matches[0] : 0.5;
    }
}
