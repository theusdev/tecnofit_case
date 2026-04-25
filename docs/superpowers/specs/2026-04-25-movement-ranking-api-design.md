# Movement Ranking API - Design Document

**Data**: 2026-04-25
**Versão**: 1.0
**Projeto**: Teste Técnico Tecnofit

## Resumo Executivo

API REST em PHP puro para gerenciamento de ranking de movimentos baseado em recordes pessoais. O sistema retorna rankings ordenados por valor decrescente, com tratamento correto de empates, e inclui um frontend de demonstração para visualização das regras de negócio.

## Requisitos

### Funcionais

1. Endpoint REST que retorna ranking de um movimento
2. Busca por ID ou nome do movimento
3. Ordenação decrescente por recorde pessoal
4. Empates compartilham a mesma posição
5. Próxima posição após empate considera todos os anteriores (1, 2, 2, 4)
6. Data do recorde pessoal corresponde ao maior valor registrado

### Não Funcionais

1. PHP 8.1 puro (sem frameworks)
2. MySQL 8.0
3. Código limpo, organizado e production-ready
4. Testes automatizados (unitários e integração)
5. Docker Compose para ambiente reproduzível
6. Análise estática (PHPStan nível 6)
7. Formatação padronizada (PHP-CS-Fixer)
8. Documentação completa

### Técnicos

1. PSR-4 autoloading via Composer
2. Strict types em todos os arquivos
3. Separação clara de responsabilidades (SRP)
4. Dependency Injection manual
5. Value Objects imutáveis
6. Tratamento de erros com exceções customizadas

## Arquitetura

### Estrutura de Pastas

```
movement-ranking/
├── public/
│   ├── index.php              # Front controller único
│   └── assets/
│       ├── app.js             # JavaScript do frontend demo
│       └── styles.css         # Estilos do frontend
├── src/
│   ├── Http/
│   │   ├── Request.php        # Abstração de HTTP request
│   │   ├── Response.php       # Abstração de HTTP response
│   │   └── Router.php         # Roteamento simples
│   ├── Controller/
│   │   ├── RankingController.php
│   │   └── HealthController.php
│   ├── Domain/
│   │   ├── RankingEntry.php   # Value object para entrada do ranking
│   │   └── MovementRanking.php # Agregado do ranking completo
│   ├── Service/
│   │   └── RankingService.php # Orquestração da lógica de negócio
│   ├── Repository/
│   │   ├── MovementRepository.php
│   │   └── PersonalRecordRepository.php
│   ├── Database/
│   │   └── Connection.php     # Gerenciamento de conexão PDO
│   └── Exception/
│       ├── ValidationException.php
│       └── NotFoundException.php
├── config/
│   ├── app.php                # Configurações gerais
│   └── database.php           # Configurações de banco
├── database/
│   ├── schema.sql             # DDL das tabelas
│   └── seed.sql               # Dados de exemplo
├── tests/
│   ├── Unit/
│   │   └── Service/
│   │       └── RankingServiceTest.php
│   └── Integration/
│       ├── Http/
│       │   └── RankingEndpointTest.php
│       └── Repository/
│           └── PersonalRecordRepositoryTest.php
├── docs/
│   ├── superpowers/
│   │   └── specs/             # Documentos de design
│   └── api-examples.http      # Exemplos de requisições HTTP
├── .env.example
├── composer.json
├── phpunit.xml
├── phpstan.neon
├── .php-cs-fixer.php
├── README.md
├── docker-compose.yml
└── Dockerfile
```

### Princípios Arquiteturais

**Separação de Responsabilidades (SRP):**
- **Http**: Lida apenas com abstração HTTP (request/response/routing)
- **Controller**: Traduz HTTP para chamadas de serviço e vice-versa
- **Service**: Orquestra lógica de negócio
- **Repository**: Acesso exclusivo ao banco de dados
- **Domain**: Objetos de domínio puros, sem dependências
- **Exception**: Exceções customizadas para casos específicos

**Dependency Injection Manual:**
Como não usamos framework, faremos DI manual no `public/index.php` (bootstrap), instanciando dependências na ordem correta e passando para os controllers.

**Namespaces PSR-4:**
- Namespace base: `App\`
- Autoload via Composer

## Componentes e Responsabilidades

### Camada HTTP

#### Request.php
- Encapsula `$_GET`, `$_POST`, `$_SERVER`
- Métodos: `getQueryParam()`, `getMethod()`, `getPath()`
- Facilita testes (mockável)

#### Response.php
- Encapsula saída JSON
- Métodos: `json($data, $statusCode)`, `error($code, $message, $statusCode)`
- Define headers apropriados (Content-Type, CORS se necessário)

#### Router.php
- Mapeia rotas para controllers
- Suporta GET com padrões simples
- Rotas: `/health`, `/api/rankings`

### Camada Controller

#### HealthController.php
- Endpoint `/health`
- Retorna `{"status": "ok", "timestamp": "..."}`
- Sem dependências

#### RankingController.php
- Endpoint `/api/rankings?movement_id=X` ou `?movement_name=Y`
- Valida parâmetros (exatamente um deve estar presente)
- Delega para `RankingService`
- Transforma resultado em JSON conforme contrato
- Trata exceções (ValidationException → 400, NotFoundException → 404)

### Camada Domain

#### RankingEntry.php (Value Object)
```php
class RankingEntry {
    public function __construct(
        public readonly int $position,
        public readonly int $userId,
        public readonly string $userName,
        public readonly float $personalRecord,
        public readonly string $recordDate
    ) {}
}
```

#### MovementRanking.php (Agregado)
```php
class MovementRanking {
    public function __construct(
        public readonly int $movementId,
        public readonly string $movementName,
        public readonly array $entries, // array de RankingEntry
        public readonly int $totalUsers
    ) {}
}
```

### Camada Service

#### RankingService.php
- Método principal: `getRanking(int|string $movementIdentifier): MovementRanking`
- Lógica:
  1. Determina se é ID ou nome
  2. Busca movimento via `MovementRepository`
  3. Se não encontrar → throw NotFoundException
  4. Busca ranking via `PersonalRecordRepository`
  5. Constrói e retorna `MovementRanking`

### Camada Repository

#### MovementRepository.php
- `findById(int $id): ?array`
- `findByName(string $name): ?array`
- Retorna array associativo ou null

#### PersonalRecordRepository.php
- `getRankingByMovementId(int $movementId): array`
- Usa Window Function (DENSE_RANK)
- Retorna array de rankings já calculados e ordenados
- A query retorna: position, user_id, user_name, personal_record, record_date

### Camada Database

#### Connection.php
- Singleton que retorna PDO
- Lê configurações de `.env`
- Configura PDO com:
  - `PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION`
  - `PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC`
  - `PDO::ATTR_EMULATE_PREPARES => false`

### Exceções

#### ValidationException.php
- Para parâmetros inválidos
- Controller captura e retorna 400

#### NotFoundException.php
- Para movimento não encontrado
- Controller captura e retorna 404

## Fluxo de Dados

### Rotas Disponíveis

**1. GET /health**
- Sem parâmetros
- Retorna status da API

**2. GET /api/rankings**
- Parâmetros (mutuamente exclusivos):
  - `movement_id` (int): ID do movimento
  - `movement_name` (string): Nome do movimento
- Retorna ranking do movimento

### Contrato de Sucesso (200 OK)

```json
{
  "data": {
    "movement": {
      "id": 1,
      "name": "Deadlift"
    },
    "ranking": [
      {
        "position": 1,
        "user": {
          "id": 2,
          "name": "Jose"
        },
        "personal_record": {
          "value": 190.0,
          "date": "2021-01-06T00:00:00Z"
        }
      },
      {
        "position": 2,
        "user": {
          "id": 1,
          "name": "Joao"
        },
        "personal_record": {
          "value": 180.0,
          "date": "2021-01-02T00:00:00Z"
        }
      },
      {
        "position": 3,
        "user": {
          "id": 3,
          "name": "Paulo"
        },
        "personal_record": {
          "value": 170.0,
          "date": "2021-01-01T00:00:00Z"
        }
      }
    ]
  },
  "meta": {
    "total_users": 3,
    "generated_at": "2026-04-25T12:30:45Z"
  }
}
```

### Contratos de Erro

**400 Bad Request - Parâmetros Inválidos**
```json
{
  "error": {
    "code": "invalid_parameters",
    "message": "Exactly one parameter required: movement_id or movement_name"
  }
}
```

**404 Not Found - Movimento Não Encontrado**
```json
{
  "error": {
    "code": "movement_not_found",
    "message": "Movement not found"
  }
}
```

**500 Internal Server Error**
```json
{
  "error": {
    "code": "internal_error",
    "message": "An unexpected error occurred"
  }
}
```

### Fluxo de Requisição (Request → Response)

1. **Request chega** → `public/index.php` (front controller)
2. **Router** identifica a rota → instancia controller apropriado
3. **Controller** valida parâmetros
   - Exatamente um presente (movement_id XOR movement_name)?
   - Se não → ValidationException → 400
4. **Controller** chama `RankingService->getRanking()`
5. **Service** determina tipo de busca (ID vs Nome)
6. **Service** chama `MovementRepository` para buscar movimento
   - Se não encontrar → NotFoundException → 404
7. **Service** chama `PersonalRecordRepository->getRankingByMovementId()`
8. **Repository** executa query com Window Function
9. **Repository** retorna array de resultados já ranqueados
10. **Service** constrói objetos de domínio (`RankingEntry`, `MovementRanking`)
11. **Service** retorna `MovementRanking` para controller
12. **Controller** transforma em array associativo conforme contrato JSON
13. **Response** serializa para JSON com status 200

### Query SQL (Coração do Sistema)

```sql
WITH personal_records AS (
    SELECT
        pr.user_id,
        MAX(pr.value) as max_value,
        (
            SELECT pr2.date
            FROM personal_record pr2
            WHERE pr2.user_id = pr.user_id
              AND pr2.movement_id = :movement_id
              AND pr2.value = MAX(pr.value)
            ORDER BY pr2.date DESC
            LIMIT 1
        ) as record_date
    FROM personal_record pr
    WHERE pr.movement_id = :movement_id
    GROUP BY pr.user_id
),
ranked_records AS (
    SELECT
        pr.user_id,
        u.name as user_name,
        pr.max_value,
        pr.record_date,
        DENSE_RANK() OVER (ORDER BY pr.max_value DESC) as position
    FROM personal_records pr
    INNER JOIN user u ON u.id = pr.user_id
)
SELECT
    position,
    user_id,
    user_name,
    max_value as personal_record,
    record_date
FROM ranked_records
ORDER BY position ASC, user_name ASC;
```

**Características da query:**
- CTE para clareza
- `MAX(value)` por usuário = recorde pessoal
- Subquery para pegar a data correta do recorde
- `DENSE_RANK()` para empates (190, 180, 180, 170 → posições 1, 2, 2, 3)
- `ORDER BY position ASC` para ordem final

## Tratamento de Erros

### Estratégia de Tratamento de Erros

**Hierarquia de Exceções:**
```
Exception (PHP nativo)
├── ValidationException (App\Exception)
│   - Parâmetros inválidos
│   - Valores fora do esperado
│   - Múltiplos parâmetros quando só um é permitido
│
├── NotFoundException (App\Exception)
│   - Movimento não encontrado por ID
│   - Movimento não encontrado por nome
│
└── DatabaseException (App\Exception) [opcional]
    - Erros de conexão
    - Erros de query
```

### Validações por Camada

**Controller (RankingController):**
- Valida presença de exatamente um parâmetro (movement_id XOR movement_name)
- Valida tipo: movement_id deve ser numérico positivo
- Valida que movement_name não está vazio
- Se inválido → throw ValidationException

**Service (RankingService):**
- Recebe dados já validados pelo controller
- Verifica se movimento existe
- Se não existe → throw NotFoundException

**Repository:**
- Apenas executa queries
- PDO em modo exception lança PDOException em caso de erro
- Não trata lógica de negócio

### Mapeamento de Exceções para HTTP Status

**No Controller (try/catch):**
```php
try {
    $ranking = $this->rankingService->getRanking($identifier);
    return $response->json($this->formatRanking($ranking), 200);
} catch (ValidationException $e) {
    return $response->error('invalid_parameters', $e->getMessage(), 400);
} catch (NotFoundException $e) {
    return $response->error('movement_not_found', $e->getMessage(), 404);
} catch (Exception $e) {
    // Log do erro real (não expor detalhes ao cliente)
    error_log($e->getMessage());
    return $response->error('internal_error', 'An unexpected error occurred', 500);
}
```

### Cenários de Erro Cobertos

1. **Nenhum parâmetro fornecido** → 400
2. **Ambos parâmetros fornecidos** → 400
3. **movement_id não numérico** → 400
4. **movement_id negativo ou zero** → 400
5. **movement_name vazio** → 400
6. **Movimento não existe (por ID)** → 404
7. **Movimento não existe (por nome)** → 404
8. **Movimento existe mas sem recordes** → 200 (ranking vazio)
9. **Erro de banco de dados** → 500 (log completo)
10. **Rota não encontrada** → 404

### Logging

**Estratégia simples:**
- Erros 500 → `error_log()` com detalhes completos
- Erros 400/404 → sem log (são esperados)
- Request ID opcional em cada requisição para rastreabilidade

## Estratégia de Testes

### Cobertura de Testes (Abordagem Focada e Pragmática)

**Testes Unitários:**
1. `RankingServiceTest.php` - Testa a orquestração da lógica de negócio

**Testes de Integração:**
1. `PersonalRecordRepositoryTest.php` - Testa a query de ranking com banco real
2. `RankingEndpointTest.php` - Testa o endpoint completo end-to-end

### Estrutura de Testes

**PHPUnit Configuration (phpunit.xml):**
```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit bootstrap="vendor/autoload.php"
         colors="true"
         testdox="true">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory>tests/Integration</directory>
        </testsuite>
    </testsuites>
    <php>
        <env name="DB_HOST" value="localhost"/>
        <env name="DB_PORT" value="3307"/>
        <env name="DB_NAME" value="ranking_test"/>
        <env name="DB_USER" value="test"/>
        <env name="DB_PASS" value="test"/>
    </php>
</phpunit>
```

### Testes Unitários - RankingServiceTest

**Cenários Cobertos:**
1. Busca por ID válido → retorna MovementRanking correto
2. Busca por nome válido → retorna MovementRanking correto
3. Movimento não encontrado por ID → lança NotFoundException
4. Movimento não encontrado por nome → lança NotFoundException
5. Movimento sem recordes → retorna ranking vazio

**Abordagem:**
- Mock de `MovementRepository` e `PersonalRecordRepository`
- Testa apenas a lógica de orquestração
- Não toca no banco de dados

### Testes de Integração - PersonalRecordRepositoryTest

**Cenários Cobertos:**
1. Ranking ordenado corretamente (ordem decrescente por valor)
2. Empates compartilham posição (190, 180, 180, 170 → 1, 2, 2, 4)
3. Data do recorde pessoal correta (retorna a data do maior valor)
4. Múltiplos recordes por usuário (retorna apenas o máximo)
5. Movimento sem recordes (retorna array vazio)

**Abordagem:**
- Banco de teste MySQL real (via Docker)
- Seed antes de cada teste
- Cleanup após cada teste
- Valida resultado da query completa

### Testes de Integração - RankingEndpointTest

**Cenários Cobertos:**
1. GET /api/rankings?movement_id=1 → 200 com ranking correto
2. GET /api/rankings?movement_name=Deadlift → 200 com ranking correto
3. GET /api/rankings (sem parâmetros) → 400
4. GET /api/rankings?movement_id=1&movement_name=Deadlift → 400
5. GET /api/rankings?movement_id=999 → 404
6. GET /api/rankings?movement_name=Inexistente → 404
7. GET /health → 200 com status ok

**Abordagem:**
- Sobe aplicação completa (bootstrap real)
- Banco de teste com seed
- Simula requisições HTTP
- Valida status code + JSON response

### Execução de Testes

**Comandos:**
```bash
# Todos os testes
docker-compose exec app composer test

# Apenas unitários (rápido)
docker-compose exec app composer test:unit

# Apenas integração
docker-compose exec app composer test:integration
```

## Frontend de Demonstração

### Estrutura do Frontend

**Arquivos:**
- `public/index.html` - Página principal
- `public/assets/app.js` - Lógica em JavaScript puro
- `public/assets/styles.css` - Estilos minimalistas

### Layout da Interface (4 Abas)

**1. Aba "Ranking"**
- Campo de busca com opções:
  - Radio buttons: "Buscar por ID" ou "Buscar por Nome"
  - Input dinâmico (number ou text)
  - Botão "Buscar Ranking"
- Tabela de resultados:
  - Colunas: Posição | Usuário | Recorde (kg) | Data do Recorde
  - Empates destacados visualmente (mesma cor de fundo)
  - Ordem decrescente por valor
- Gráfico de barras horizontal
  - Eixo X: Valor do recorde
  - Eixo Y: Nome do usuário
  - Cores diferentes por posição

**2. Aba "Resposta JSON"**
- Exibe o JSON retornado pela API
- Formatado e com syntax highlighting
- Botão "Copiar JSON"
- Útil para avaliadores técnicos

**3. Aba "Regras de Negócio"**
- Explicação visual das regras:
  - "O sistema agrupa recordes por usuário"
  - "Seleciona o maior valor de cada usuário"
  - "Ordena do maior para o menor"
  - "Empates compartilham a mesma posição"
  - "A próxima posição pula conforme ranking real (1, 2, 2, 4)"
- Exemplo com dados do movimento selecionado

**4. Aba "Testes Cobertos"**
- Lista de cenários testados:
  - Ranking por ID do movimento
  - Ranking por nome do movimento
  - Empates compartilham posição
  - Próxima posição após empate
  - Movimento inexistente retorna 404
  - Movimento sem recordes retorna ranking vazio
  - Data do recorde pessoal
  - Parâmetros inválidos retornam 400
  - Query utiliza DENSE_RANK
  - Ordenação decrescente por valor

### Tecnologias

- JavaScript Puro (Vanilla JS)
- Fetch API para requisições
- DOM manipulation
- CSS Puro (Flexbox/Grid)
- Canvas API para gráficos (sem bibliotecas externas)

### Roteamento Frontend/API

No `public/index.php`:
```php
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Se for rota de API, processa normalmente
if (str_starts_with($path, '/api/') || $path === '/health') {
    $router->dispatch($request, $response);
} else {
    // Serve o frontend
    if (file_exists(__DIR__ . '/index.html')) {
        readfile(__DIR__ . '/index.html');
    }
}
```

## Ambiente de Desenvolvimento

### Docker Compose

**Serviços:**
- `app` (PHP 8.1-FPM)
- `nginx` (servidor web)
- `mysql` (MySQL 8.0)

**Portas:**
- Aplicação: 8080
- MySQL: 3306

**Volumes:**
- Código fonte montado em `/var/www`
- Dados MySQL persistidos em volume nomeado
- Schema e seed executados automaticamente na inicialização

### Ferramentas de Qualidade

**PHPStan (nível 6):**
- Análise estática de tipos
- Detecção de erros potenciais
- Configuração em `phpstan.neon`

**PHP-CS-Fixer:**
- Formatação automática de código
- Padrão PSR-12
- Configuração em `.php-cs-fixer.php`

**PHPUnit:**
- Framework de testes
- Suporte a testes unitários e integração
- Configuração em `phpunit.xml`

### Composer

**Dependências:**
- `php: ^8.1`
- `ext-pdo`
- `ext-json`

**Dependências de Desenvolvimento:**
- `phpunit/phpunit: ^10.0`
- `phpstan/phpstan: ^1.10`
- `friendsofphp/php-cs-fixer: ^3.0`

**Scripts:**
```json
{
  "test": "phpunit",
  "test:unit": "phpunit --testsuite=Unit",
  "test:integration": "phpunit --testsuite=Integration",
  "phpstan": "phpstan analyze src tests --level=6",
  "cs-fix": "php-cs-fixer fix",
  "cs-check": "php-cs-fixer fix --dry-run --diff",
  "quality": ["@phpstan", "@cs-check", "@test"]
}
```

### Variáveis de Ambiente

**.env.example:**
```env
# Application
APP_ENV=development
APP_DEBUG=true

# Database
DB_HOST=mysql
DB_PORT=3306
DB_NAME=ranking
DB_USER=ranking_user
DB_PASS=ranking_pass

# Database Test
DB_TEST_HOST=mysql
DB_TEST_PORT=3306
DB_TEST_NAME=ranking_test
DB_TEST_USER=ranking_user
DB_TEST_PASS=ranking_pass
```

## Decisões Técnicas

### Por que Window Function (DENSE_RANK)?

MySQL 8 oferece window functions que permitem calcular rankings com empates de forma nativa e performática. A alternativa seria processar em PHP, o que seria menos eficiente e mais propenso a erros.

**Vantagens:**
- Performance (processamento no banco)
- Código mais limpo (lógica de ranking no SQL)
- Demonstra conhecimento de recursos modernos
- Calcula empates corretamente de forma nativa

### Por que PHP puro sem framework?

Requisito do desafio. Implementamos abstrações mínimas (Request, Response, Router) para manter organização sem adicionar complexidade desnecessária.

### Por que separar Domain de Repository?

Manter objetos de domínio puros e desacoplados facilita testes e manutenção. Repositories retornam arrays, Services constroem objetos de domínio.

### Tratamento de data do recorde

Como um usuário pode ter múltiplos recordes com o mesmo valor máximo, usamos subquery para pegar a data mais recente daquele valor.

### Frontend de demonstração

Incluído para facilitar visualização das regras de negócio e validação manual pelos avaliadores. Implementado em JavaScript puro sem frameworks.

## Regras de Negócio

1. **Recorde Pessoal**: Maior valor registrado por um usuário em um movimento
2. **Ranking**: Ordenação decrescente por recorde pessoal
3. **Empates**: Usuários com mesmo valor compartilham a mesma posição
4. **Posições após empate**: Próxima posição considera todos os anteriores
   - Exemplo: 190, 180, 180, 170 → posições 1, 2, 2, 4
5. **Data do recorde**: Data correspondente ao maior valor (mais recente se houver múltiplos)

## Melhorias Futuras

Se este fosse um projeto real, consideraria:
- Cache de rankings com Redis
- Paginação de resultados
- Filtros adicionais (por data, por usuário)
- Autenticação e autorização
- Rate limiting
- Logs estruturados (JSON)
- Métricas e monitoring (Prometheus)
- CI/CD pipeline
- API versioning
- CORS configurável
- Compressão de resposta (gzip)

## Estrutura do Banco de Dados

### Tabelas

**user**
- `id` (PK, auto_increment)
- `name` (varchar 255)

**movement**
- `id` (PK, auto_increment)
- `name` (varchar 255)

**personal_record**
- `id` (PK, auto_increment)
- `user_id` (FK → user.id)
- `movement_id` (FK → movement.id)
- `value` (float)
- `date` (datetime)

### Dados de Exemplo

3 usuários, 3 movimentos, 17 recordes pessoais distribuídos conforme seed fornecido no desafio.

## Checklist de Implementação

- [ ] Configurar ambiente Docker
- [ ] Criar estrutura de pastas
- [ ] Configurar Composer e autoload PSR-4
- [ ] Implementar camada HTTP (Request, Response, Router)
- [ ] Implementar camada Database (Connection)
- [ ] Implementar camada Domain (RankingEntry, MovementRanking)
- [ ] Implementar camada Repository (MovementRepository, PersonalRecordRepository)
- [ ] Implementar query SQL com Window Function
- [ ] Implementar camada Service (RankingService)
- [ ] Implementar camada Controller (HealthController, RankingController)
- [ ] Implementar Exceções (ValidationException, NotFoundException)
- [ ] Implementar front controller (public/index.php)
- [ ] Configurar PHPUnit
- [ ] Escrever testes unitários (RankingServiceTest)
- [ ] Escrever testes de integração (PersonalRecordRepositoryTest)
- [ ] Escrever testes de integração (RankingEndpointTest)
- [ ] Configurar PHPStan
- [ ] Configurar PHP-CS-Fixer
- [ ] Implementar frontend HTML/CSS/JS
- [ ] Implementar aba Ranking
- [ ] Implementar aba JSON
- [ ] Implementar aba Regras de Negócio
- [ ] Implementar aba Testes Cobertos
- [ ] Implementar gráfico de barras
- [ ] Escrever README completo
- [ ] Escrever exemplos HTTP (docs/api-examples.http)
- [ ] Criar .env.example
- [ ] Executar todos os testes
- [ ] Executar PHPStan
- [ ] Executar PHP-CS-Fixer
- [ ] Validar Docker Compose
- [ ] Validar frontend demo
- [ ] Revisar commits
- [ ] Validação final

## Conclusão

Este design apresenta uma arquitetura sólida, escalável e profissional para uma API REST de ranking de movimentos. A separação clara de responsabilidades, o uso de window functions do MySQL 8, a cobertura de testes focada e o frontend de demonstração garantem que o projeto atenda aos requisitos técnicos do desafio e demonstre maturidade profissional.

A implementação seguirá estritamente este design, priorizando código limpo, testável e production-ready.
