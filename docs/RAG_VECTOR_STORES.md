# RAG & Vector Stores

## Overview

Retrieval-Augmented Generation (RAG) enables AI agents to retrieve relevant information from a knowledge base before generating responses. Vector stores provide semantic search capabilities for efficient information retrieval.

## Architecture

```
┌─────────────────────────────────────────────────────────┐
│                    User Query                          │
└───────────────────┬─────────────────────────────────────┘
                    │
        ┌───────────▼───────────┐
        │   Query Embedding    │
        │   (Vectorization)     │
        └───────────┬───────────┘
                    │
        ┌───────────▼───────────┐
        │   Vector Store        │
        │   (Similarity Search) │
        └───────────┬───────────┘
                    │
        ┌───────────▼───────────┐
        │   Retrieved Context   │
        └───────────┬───────────┘
                    │
        ┌───────────▼───────────┐
        │   LLM Agent          │
        │   (Generate Response) │
        └───────────────────────┘
```

## Supported Vector Stores

- ✅ **Pinecone** - Managed vector database
- ✅ **Weaviate** - Open-source vector database
- ✅ **Qdrant** - Vector similarity search engine
- ✅ **Chroma** - Open-source embedding database
- ✅ **Milvus** - Vector database for AI applications
- ✅ **PostgreSQL (pgvector)** - Vector extension for PostgreSQL
- ✅ **WordPress Native** - Built-in vector storage using WordPress database

## Vector Store Configuration

### Pinecone Configuration

Create `configs/integrations/pinecone.json`:

```json
{
  "id": "pinecone",
  "name": "Pinecone",
  "description": "Pinecone vector database integration",
  "version": "1.0.0",
  "icon": "pinecone",
  "authentication": {
    "type": "api_key",
    "header_name": "Api-Key",
    "header_format": "{api_key}",
    "storage": "encrypted"
  },
  "inputs": {
    "environment": {
      "type": "string",
      "required": true,
      "label": "Environment",
      "description": "Pinecone environment (e.g., us-east-1-aws)"
    }
  },
  "tools": [
    "pinecone_upsert_vectors",
    "pinecone_query_vectors",
    "pinecone_delete_vectors",
    "pinecone_create_index",
    "pinecone_list_indexes"
  ],
  "api_base_url": "https://{environment}.pinecone.io",
  "settings": {
    "default_dimension": 1536,
    "default_metric": "cosine"
  }
}
```

### Weaviate Configuration

Create `configs/integrations/weaviate.json`:

```json
{
  "id": "weaviate",
  "name": "Weaviate",
  "description": "Weaviate vector database integration",
  "version": "1.0.0",
  "icon": "weaviate",
  "authentication": {
    "type": "api_key",
    "header_name": "Authorization",
    "header_format": "Bearer {api_key}",
    "storage": "encrypted",
    "optional": true
  },
  "inputs": {
    "weaviate_url": {
      "type": "string",
      "required": true,
      "label": "Weaviate URL",
      "description": "Weaviate instance URL (e.g., https://your-cluster.weaviate.network)"
    }
  },
  "tools": [
    "weaviate_create_class",
    "weaviate_add_objects",
    "weaviate_query",
    "weaviate_delete_objects",
    "weaviate_get_schema"
  ],
  "api_base_url": "{weaviate_url}/v1",
  "settings": {
    "default_vectorizer": "text2vec-openai"
  }
}
```

### Qdrant Configuration

Create `configs/integrations/qdrant.json`:

```json
{
  "id": "qdrant",
  "name": "Qdrant",
  "description": "Qdrant vector database integration",
  "version": "1.0.0",
  "icon": "qdrant",
  "authentication": {
    "type": "api_key",
    "header_name": "api-key",
    "header_format": "{api_key}",
    "storage": "encrypted",
    "optional": true
  },
  "inputs": {
    "qdrant_url": {
      "type": "string",
      "required": true,
      "label": "Qdrant URL",
      "description": "Qdrant instance URL"
    }
  },
  "tools": [
    "qdrant_create_collection",
    "qdrant_upsert_points",
    "qdrant_search_points",
    "qdrant_delete_points",
    "qdrant_list_collections"
  ],
  "api_base_url": "{qdrant_url}",
  "settings": {
    "default_distance": "Cosine"
  }
}
```

### Chroma Configuration

Create `configs/integrations/chroma.json`:

```json
{
  "id": "chroma",
  "name": "Chroma",
  "description": "Chroma vector database integration",
  "version": "1.0.0",
  "icon": "chroma",
  "authentication": {
    "type": "api_key",
    "header_name": "X-Chroma-Token",
    "header_format": "{api_key}",
    "storage": "encrypted",
    "optional": true
  },
  "inputs": {
    "chroma_url": {
      "type": "string",
      "required": true,
      "label": "Chroma URL",
      "description": "Chroma instance URL"
    }
  },
  "tools": [
    "chroma_create_collection",
    "chroma_add_documents",
    "chroma_query",
    "chroma_delete",
    "chroma_list_collections"
  ],
  "api_base_url": "{chroma_url}/api/v1"
}
```

### WordPress Native Vector Store

Create `configs/integrations/wordpress-vector-store.json`:

```json
{
  "id": "wordpress_vector_store",
  "name": "WordPress Vector Store",
  "description": "Native WordPress vector storage using database",
  "version": "1.0.0",
  "icon": "wordpress",
  "authentication": {
    "type": "none"
  },
  "tools": [
    "wp_vector_store_add",
    "wp_vector_store_search",
    "wp_vector_store_delete",
    "wp_vector_store_create_index"
  ],
  "settings": {
    "table_name": "wp_aw_vector_store",
    "default_dimension": 1536,
    "use_faiss": false
  }
}
```

## Embedding Providers

Vector stores require embeddings. Configure embedding providers:

### OpenAI Embeddings

Create `configs/integrations/openai-embeddings.json`:

```json
{
  "id": "openai_embeddings",
  "name": "OpenAI Embeddings",
  "description": "OpenAI text embedding integration",
  "version": "1.0.0",
  "icon": "openai",
  "authentication": {
    "type": "api_key",
    "header_name": "Authorization",
    "header_format": "Bearer {api_key}",
    "storage": "encrypted"
  },
  "tools": [
    "openai_create_embedding"
  ],
  "api_base_url": "https://api.openai.com/v1",
  "settings": {
    "default_model": "text-embedding-3-small",
    "dimension": 1536
  }
}
```

### Other Embedding Providers

- **Cohere Embeddings**
- **Hugging Face Embeddings**
- **Sentence Transformers** (local)
- **Google Embeddings**

## RAG Agent Configuration

Create `configs/agents/rag-agent.json`:

```json
{
  "id": "rag_agent",
  "name": "RAG Agent",
  "description": "Retrieval-Augmented Generation agent",
  "version": "1.0.0",
  "category": "ai",
  "icon": "brain",
  "capabilities": [
    "retrieval_augmented_generation",
    "semantic_search",
    "context_aware_responses"
  ],
  "inputs": {
    "query": {
      "type": "string",
      "required": true,
      "label": "Query",
      "description": "User query or question"
    },
    "vector_store": {
      "type": "enum",
      "required": true,
      "label": "Vector Store",
      "options": [
        "pinecone",
        "weaviate",
        "qdrant",
        "chroma",
        "wordpress_vector_store"
      ]
    },
    "collection_name": {
      "type": "string",
      "required": true,
      "label": "Collection/Index Name"
    },
    "top_k": {
      "type": "integer",
      "required": false,
      "default": 5,
      "label": "Top K Results",
      "description": "Number of relevant documents to retrieve"
    },
    "llm_provider": {
      "type": "enum",
      "required": false,
      "default": "openai",
      "label": "LLM Provider",
      "options": ["openai", "anthropic", "google_gemini"]
    },
    "llm_model": {
      "type": "string",
      "required": false,
      "label": "LLM Model",
      "description": "Specific model to use (uses provider default if not specified)"
    },
    "temperature": {
      "type": "float",
      "required": false,
      "default": 0.7,
      "label": "Temperature"
    }
  },
  "outputs": {
    "response": {
      "type": "string",
      "label": "Response"
    },
    "sources": {
      "type": "array",
      "label": "Sources",
      "description": "Retrieved document sources"
    },
    "retrieved_documents": {
      "type": "array",
      "label": "Retrieved Documents"
    },
    "confidence_score": {
      "type": "float",
      "label": "Confidence Score"
    }
  },
  "handler": "Agents\\RAG\\RAGAgent",
  "settings": {
    "rerank_results": false,
    "include_metadata": true,
    "max_context_length": 4000
  }
}
```

## Tool Configurations

### Upsert Vectors Tool (Pinecone)

Create `configs/tools/pinecone-upsert-vectors.json`:

```json
{
  "id": "pinecone_upsert_vectors",
  "name": "Upsert Vectors to Pinecone",
  "description": "Adds or updates vectors in Pinecone index",
  "version": "1.0.0",
  "category": "vector-store",
  "icon": "pinecone",
  "integration": "pinecone",
  "inputs": {
    "index_name": {
      "type": "string",
      "required": true,
      "label": "Index Name"
    },
    "vectors": {
      "type": "array",
      "required": true,
      "label": "Vectors",
      "description": "Array of vector objects with id, values, and metadata",
      "items": {
        "type": "object",
        "properties": {
          "id": "string",
          "values": "array",
          "metadata": "object"
        }
      }
    },
    "namespace": {
      "type": "string",
      "required": false,
      "label": "Namespace"
    }
  },
  "outputs": {
    "upserted_count": {
      "type": "integer",
      "label": "Upserted Count"
    }
  },
  "handler": "Integrations\\Pinecone\\Tools\\UpsertVectors"
}
```

### Query Vectors Tool (Pinecone)

Create `configs/tools/pinecone-query-vectors.json`:

```json
{
  "id": "pinecone_query_vectors",
  "name": "Query Pinecone Vectors",
  "description": "Searches for similar vectors in Pinecone",
  "version": "1.0.0",
  "category": "vector-store",
  "icon": "pinecone",
  "integration": "pinecone",
  "inputs": {
    "index_name": {
      "type": "string",
      "required": true,
      "label": "Index Name"
    },
    "vector": {
      "type": "array",
      "required": true,
      "label": "Query Vector",
      "description": "Vector to search for",
      "items": {
        "type": "number"
      }
    },
    "top_k": {
      "type": "integer",
      "required": false,
      "default": 10,
      "label": "Top K",
      "description": "Number of results to return"
    },
    "include_metadata": {
      "type": "boolean",
      "required": false,
      "default": true,
      "label": "Include Metadata"
    },
    "filter": {
      "type": "object",
      "required": false,
      "label": "Filter",
      "description": "Metadata filter"
    }
  },
  "outputs": {
    "matches": {
      "type": "array",
      "label": "Matches",
      "description": "Array of matching vectors with scores"
    }
  },
  "handler": "Integrations\\Pinecone\\Tools\\QueryVectors"
}
```

## RAG Workflow Examples

### Basic RAG Workflow

```
Trigger: User Query
  ↓
Tool: Create Embedding (Query)
  ↓
Tool: Query Vector Store
  ↓
Tool: Retrieve Top K Documents
  ↓
Agent: RAG Agent (Generate Response with Context)
  ↓
Tool: Return Response with Sources
```

### Document Ingestion Workflow

```
Trigger: New Document Added
  ↓
Tool: Split Document into Chunks
  ↓
Tool: Create Embeddings for Chunks
  ↓
Tool: Upsert Vectors to Store
  ↓
Tool: Update Index Metadata
```

### Multi-Source RAG Workflow

```
Trigger: User Query
  ↓
Tool: Create Embedding
  ↓
Parallel:
  ├─ Tool: Query Pinecone (Knowledge Base)
  ├─ Tool: Query WordPress Posts
  └─ Tool: Query External API
  ↓
Tool: Merge and Rank Results
  ↓
Agent: RAG Agent (Generate Response)
```

## Handler Class Example

Create `includes/agents/rag/class-rag-agent.php`:

```php
<?php
namespace Agents\RAG;

use WP_Agentic_Workflow\Agents\Agent_Base;

class RAG_Agent extends Agent_Base {
    
    public function execute($inputs) {
        $query = $inputs['query'];
        $vector_store_id = $inputs['vector_store'];
        $collection_name = $inputs['collection_name'];
        $top_k = $inputs['top_k'] ?? 5;
        
        // Get embedding for query
        $embedding_provider = $this->get_embedding_provider();
        $query_embedding = $embedding_provider->create_embedding($query);
        
        // Query vector store
        $vector_store = $this->get_vector_store($vector_store_id);
        $results = $vector_store->query($collection_name, $query_embedding, $top_k);
        
        // Build context from retrieved documents
        $context = $this->build_context($results);
        
        // Generate response using LLM with context
        $llm_provider = $this->get_llm_provider($inputs['llm_provider'] ?? 'openai');
        $response = $llm_provider->chat([
            [
                'role' => 'system',
                'content' => "You are a helpful assistant. Use the following context to answer the question:\n\n{$context}"
            ],
            [
                'role' => 'user',
                'content' => $query
            ]
        ], [
            'model' => $inputs['llm_model'] ?? null,
            'temperature' => $inputs['temperature'] ?? 0.7
        ]);
        
        return [
            'response' => $response['content'],
            'sources' => $this->extract_sources($results),
            'retrieved_documents' => $results,
            'confidence_score' => $this->calculate_confidence($results)
        ];
    }
    
    protected function build_context($results) {
        $context_parts = [];
        foreach ($results as $result) {
            $context_parts[] = $result['metadata']['text'] ?? $result['text'];
        }
        return implode("\n\n", $context_parts);
    }
    
    protected function extract_sources($results) {
        $sources = [];
        foreach ($results as $result) {
            if (isset($result['metadata']['source'])) {
                $sources[] = $result['metadata']['source'];
            }
        }
        return array_unique($sources);
    }
    
    protected function calculate_confidence($results) {
        if (empty($results)) {
            return 0.0;
        }
        
        // Average similarity scores
        $scores = array_column($results, 'score');
        return array_sum($scores) / count($scores);
    }
}
```

## Use Cases

### 1. Knowledge Base Q&A

Store documentation, FAQs, and knowledge articles in a vector store. Users can ask questions and get answers based on the stored knowledge.

### 2. Document Search

Index documents (PDFs, Word docs, etc.) and enable semantic search to find relevant information.

### 3. WordPress Content Assistant

Index WordPress posts, pages, and custom post types. Create an AI assistant that can answer questions about your site's content.

### 4. Customer Support

Store support articles and FAQs. Automatically retrieve relevant information to help answer customer queries.

### 5. Code Documentation

Index code documentation and provide AI-powered code assistance with context from your codebase.

## Best Practices

1. **Chunk Size**: Use appropriate chunk sizes (typically 500-1000 tokens)
2. **Overlap**: Add overlap between chunks to preserve context
3. **Metadata**: Include rich metadata (source, title, date, etc.)
4. **Indexing**: Regularly update indexes as content changes
5. **Filtering**: Use metadata filters to narrow search scope
6. **Reranking**: Consider reranking results for better relevance
7. **Hybrid Search**: Combine vector search with keyword search when appropriate

## Performance Optimization

- **Batch Operations**: Batch vector upserts for better performance
- **Caching**: Cache frequently accessed embeddings
- **Indexing**: Use appropriate index types for your use case
- **Connection Pooling**: Reuse connections to vector stores
- **Async Operations**: Use async operations for large document ingestion

