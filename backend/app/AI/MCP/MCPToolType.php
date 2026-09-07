<?php

namespace App\AI\MCP;

enum MCPToolType: string
{
    case RESOURCE = 'resource';
    case PROMPT = 'prompt';
    case TOOL = 'tool';
}
