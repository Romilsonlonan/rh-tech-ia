<?php

namespace App\AI\Agents;

use App\AI\KnowledgeBase\RAGPipeline;
use App\AI\KnowledgeBase\LLMServiceInterface;

abstract class BaseAgent implements AgentInterface
{
    protected RAGPipeline $rag;
    protected LLMServiceInterface $llm;
    protected array $config = [];

    public function __construct(
        RAGPipeline $rag,
        LLMServiceInterface $llm
    ) {
        $this->rag = $rag;
        $this->llm = $llm;
        $this->config = $this->getDefaultConfig();
    }

    abstract public function getName(): string;
    abstract public function getDescription(): string;
    abstract protected function getDefaultConfig(): array;
    abstract protected function executeInternal(array $context): AgentResponse;

    public function execute(array $context = []): AgentResponse
    {
        try {
            $this->validateContext($context);
            return $this->executeInternal($context);
        } catch (\Exception $e) {
            return AgentResponse::failure($e->getMessage());
        }
    }

    protected function validateContext(array $context): void
    {
        $required = $this->requiredContextKeys();
        foreach ($required as $key) {
            if (!isset($context[$key])) {
                throw new \InvalidArgumentException("Missing required context key: {$key}");
            }
        }
    }

    protected function requiredContextKeys(): array
    {
        return [];
    }

    protected function ragQuery(string $question): string
    {
        return $this->rag->query($question);
    }
}
