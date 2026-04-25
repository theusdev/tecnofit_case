# Movement Ranking API

API REST desenvolvida em PHP puro para gerenciar e exibir rankings de movimentos baseados em recordes pessoais.

## Descrição

Esta API foi desenvolvida como parte do desafio técnico da Tecnofit. O sistema permite:

- Listar todos os movimentos cadastrados
- Obter ranking de usuários por movimento, ordenado por recorde pessoal
- Suportar empates no ranking (mesma posição para mesmos valores)
- Filtrar resultados com limite configurável

## Stack Técnico

- **PHP**: 8.1+ (strict types, readonly properties)
- **MySQL**: 8.0+ (Window Functions - DENSE_RANK)
- **Docker**: Compose para ambiente isolado
- **Nginx**: 1.21+ como servidor web
- **PHPUnit**: 10.0+ para testes automatizados
- **PHPStan**: Level 6 para análise estática
- **PHP-CS-Fixer**: PSR-12 para formatação de código

## Arquitetura

O projeto segue uma arquitetura em camadas:

```
src/
├── Controller/     # Recebe requisições HTTP e retorna respostas
├── Service/        # Lógica de negócio e validações
├── Repository/     # Acesso a dados com queries SQL
├── Domain/         # Entidades e objetos de domínio
├── Http/           # Componentes HTTP (Request, Response, Router)
├── Database/       # Gerenciamento de conexão PDO
└── Exception/      # Exceções personalizadas
```

### Padrões Utilizados

- **Dependency Injection Manual**: Sem uso de containers
- **Value Objects**: RankingEntry (imutável)
- **Aggregates**: MovementRanking
- **Repository Pattern**: Separação de acesso a dados
- **PSR-4 Autoloading**: Composer autoload
- **Strict Types**: Todas as classes usam `declare(strict_types=1)`

## Instalação

### Pré-requisitos

- Docker e Docker Compose instalados
- Git

### Passos

1. Clone o repositório:
```bash
git clone <repository-url>
cd tecnofit-case
```

2. Copie o arquivo de ambiente:
```bash
cp .env.example .env
```

3. Suba os containers:
```bash
docker-compose up -d
```

4. Instale as dependências:
```bash
docker-compose exec app composer install
```

5. Acesse a aplicação:
- API: http://localhost:8080
- Frontend Demo: http://localhost:8080/demo.html

## Uso da API

### Endpoints Disponíveis

#### 1. Health Check
```
GET /health
```
Retorna o status da API.

**Resposta:**
```json
{
  "status": "ok",
  "timestamp": "2024-01-15 10:30:00"
}
```

#### 2. Listar Movimentos
```
GET /movements
```
Retorna todos os movimentos cadastrados.

**Resposta:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Deadlift"
    },
    {
      "id": 2,
      "name": "Back Squat"
    }
  ]
}
```

#### 3. Obter Ranking
```
GET /ranking?movement_id={id}&limit={limit}
```

**Parâmetros:**
- `movement_id` (obrigatório): ID do movimento
- `limit` (opcional): Número máximo de resultados

**Resposta:**
```json
{
  "data": {
    "movement_id": 1,
    "movement_name": "Deadlift",
    "total_users": 3,
    "ranking": [
      {
        "position": 1,
        "user_id": 1,
        "user_name": "Joao",
        "personal_record": 180.0,
        "record_date": "2021-01-04"
      }
    ]
  }
}
```

### Tratamento de Erros

A API retorna erros no formato:
```json
{
  "error": {
    "code": "validation_error",
    "message": "O parâmetro movement_id é obrigatório"
  }
}
```

**Códigos de erro:**
- `validation_error` (400): Erro de validação nos parâmetros
- `not_found` (404): Recurso não encontrado
- `internal_error` (500): Erro interno do servidor

## Testes

### Executar Testes

```bash
# Todos os testes
docker-compose exec app vendor/bin/phpunit

# Apenas testes de integração
docker-compose exec app vendor/bin/phpunit --testsuite=Integration

# Com cobertura
docker-compose exec app vendor/bin/phpunit --coverage-html coverage
```

### Análise Estática

```bash
# PHPStan
docker-compose exec app vendor/bin/phpstan analyse

# PHP-CS-Fixer (verificar)
docker-compose exec app vendor/bin/php-cs-fixer fix --dry-run --diff

# PHP-CS-Fixer (corrigir)
docker-compose exec app vendor/bin/php-cs-fixer fix
```

## Banco de Dados

### Schema

O banco possui 3 tabelas principais:

- **user**: Cadastro de usuários
- **movement**: Tipos de movimento (Deadlift, Back Squat, etc.)
- **personal_record**: Recordes pessoais de cada usuário por movimento

### Seed Data

O banco é inicializado com dados de exemplo:
- 3 usuários (Joao, Jose, Paulo)
- 3 movimentos (Deadlift, Back Squat, Bench Press)
- 17 recordes pessoais

### Window Functions

O ranking utiliza a função `DENSE_RANK()` do MySQL 8:
```sql
DENSE_RANK() OVER (ORDER BY pr.value DESC) as position
```

Isso garante que:
- Usuários com o mesmo recorde recebem a mesma posição
- Não há saltos na numeração (1, 2, 2, 3 ao invés de 1, 2, 2, 4)

## Frontend Demo

Acesse http://localhost:8080/demo.html para visualizar a interface de demonstração com:
- Health Check
- Lista de movimentos
- Ranking do Deadlift
- Ranking do Back Squat

## Estrutura de Diretórios

```
.
├── config/                 # Arquivos de configuração
├── database/              # Schema e seeds SQL
├── docker/                # Configurações Docker
├── docs/                  # Documentação
├── public/                # Ponto de entrada HTTP
│   ├── css/              # Estilos do frontend
│   ├── js/               # JavaScript do frontend
│   ├── demo.html         # Interface de demonstração
│   └── index.php         # Front controller
├── src/                   # Código-fonte
├── tests/                 # Testes automatizados
├── .php-cs-fixer.php     # Configuração formatação
├── phpstan.neon          # Configuração análise estática
├── phpunit.xml           # Configuração testes
├── composer.json         # Dependências PHP
└── docker-compose.yml    # Orquestração containers
```

## Qualidade de Código

- **PSR-12**: Padrão de codificação seguido
- **Strict Types**: Tipagem estrita em todos os arquivos
- **PHPStan Level 6**: Análise estática rigorosa
- **Readonly Properties**: Imutabilidade onde aplicável
- **SOLID Principles**: Aplicados na arquitetura

## Decisões Técnicas

### Por que PHP Puro?

- Demonstrar conhecimento profundo da linguagem
- Evitar overhead de frameworks
- Controle total sobre a arquitetura
- Código mais transparente para avaliação

### Por que Window Functions?

- Performance superior vs. subqueries
- Código SQL mais limpo e legível
- Suporte nativo do MySQL 8
- Cálculo de ranking em uma única query

### Por que Dependency Injection Manual?

- Transparência total do código
- Sem magia ou abstrações desnecessárias
- Facilita debugging e compreensão
- Adequado para aplicações de porte pequeno/médio

## Autor

Desenvolvido como parte do desafio técnico Tecnofit.

## Licença

Este projeto foi desenvolvido para fins de avaliação técnica.
