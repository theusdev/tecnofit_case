# Ranking de Movimentos API

API REST desenvolvida em PHP puro para retornar o ranking de um movimento com base no maior recorde pessoal de cada usuario.

O projeto foi construído para o case técnico da Tecnofit e inclui:

- endpoint REST para buscar ranking por `movement_id` ou `movement_name`
- tratamento de empate no ranking
- MySQL 8 com dados iniciais do desafio
- frontend de demonstracao para visualizar a API em funcionamento
- testes automatizados e organizacao por camadas

## O que este projeto resolve

Dado um movimento, a API retorna:

- nome do movimento
- lista ordenada de usuarios
- recorde pessoal de cada usuario naquele movimento
- posicao no ranking
- data do recorde pessoal

Regras aplicadas:

- o ranking considera apenas o maior recorde de cada usuario para o movimento consultado
- a ordenacao e decrescente pelo valor do recorde
- usuarios com o mesmo valor compartilham a mesma posicao
- apos empate, a posicao seguinte respeita o comportamento de ranking competitivo: `1, 2, 2, 4`

## Stack

- PHP 8.1
- MySQL 8
- Nginx
- Docker e Docker Compose
- PHPUnit
- PHPStan
- PHP-CS-Fixer
- JavaScript puro no frontend de demonstracao

## Estrutura do projeto

```text
.
├── config/                  # Configuracoes da aplicacao e banco
├── database/                # Schema e seed do MySQL
├── docker/                  # Configuracao do Nginx
├── docs/                    # Documentacao complementar
├── public/                  # Entrada HTTP e frontend de demonstracao
├── src/
│   ├── Controller/          # Camada HTTP
│   ├── Database/            # Conexao PDO
│   ├── Domain/              # Objetos de dominio
│   ├── Exception/           # Excecoes da aplicacao
│   ├── Http/                # Request, Response e Router
│   ├── Repository/          # Acesso a dados
│   └── Service/             # Regras de negocio
├── tests/                   # Testes unitarios e de integracao
├── composer.json
├── docker-compose.yml
└── README.md
```

## Como rodar localmente

### 1. Dependencias

Antes de qualquer comando do projeto, instale:

- Git
- Docker Desktop

No Windows, o Docker Desktop precisa estar instalado e em execucao antes de subir os containers.

Documentacao oficial:

- [Docker Desktop](https://docs.docker.com/desktop/setup/install/windows-install/)

### 2. Clonar o repositorio

```bash
git clone <repository-url>
cd tecnofit-case
```

### 3. Subir o Docker Desktop

Abra o Docker Desktop e confirme que ele esta em execucao.

### 4. Copiar o arquivo de ambiente

```bash
cp .env.example .env
```

Se estiver no PowerShell:

```powershell
Copy-Item .env.example .env
```

### 5. Subir os containers

```bash
docker compose up -d --build
```

Esse comando sobe:

- `app`: PHP-FPM
- `nginx`: servidor HTTP
- `mysql`: banco de dados MySQL 8 com schema e seed

### 6. Instalar dependencias do Composer dentro do container

```bash
docker compose exec app composer install
```

### 7. Acessar a aplicacao

- Frontend de demonstracao: [http://localhost:8080](http://localhost:8080)
- Health check: [http://localhost:8080/health](http://localhost:8080/health)
- Lista de movimentos: [http://localhost:8080/movements](http://localhost:8080/movements)

## Como usar a API

### Buscar ranking por id

```http
GET /api/rankings?movement_id=1
```

Exemplo:

```bash
curl "http://localhost:8080/api/rankings?movement_id=1"
```

### Buscar ranking por nome

```http
GET /api/rankings?movement_name=Deadlift
```

Exemplo:

```bash
curl "http://localhost:8080/api/rankings?movement_name=Deadlift"
```

### Parametros aceitos

- `movement_id`: identificador numerico do movimento
- `movement_name`: nome do movimento
- `limit`: opcional, limita a quantidade de usuarios retornados

Observacao:

- envie `movement_id` ou `movement_name`
- nao envie os dois ao mesmo tempo

## Exemplo de resposta

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
          "value": 190,
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
          "value": 180,
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
          "value": 170,
          "date": "2021-01-01T00:00:00Z"
        }
      }
    ]
  },
  "meta": {
    "total_users": 3,
    "generated_at": "2026-04-27T00:00:00Z"
  }
}
```

## Erros da API

### 400 - parametros invalidos

```json
{
  "error": {
    "code": "parametros_invalidos",
    "message": "Exatamente um parametro e obrigatorio: movement_id ou movement_name"
  }
}
```

### 404 - movimento nao encontrado

```json
{
  "error": {
    "code": "movimento_nao_encontrado",
    "message": "Movimento com ID 999 nao encontrado"
  }
}
```

### 500 - erro interno

```json
{
  "error": {
    "code": "erro_interno",
    "message": "Ocorreu um erro inesperado"
  }
}
```

## Testes e qualidade

Executar testes:

```bash
docker compose exec app php vendor/bin/phpunit
```

Executar apenas testes unitarios:

```bash
docker compose exec app php vendor/bin/phpunit --testsuite=Unit
```

Executar apenas testes de integracao:

```bash
docker compose exec app php vendor/bin/phpunit --testsuite=Integration
```

Executar analise estatica:

```bash
docker compose exec app php vendor/bin/phpstan analyse src tests --level=6
```

Executar verificacao de estilo:

```bash
docker compose exec app php vendor/bin/php-cs-fixer fix --dry-run --diff --allow-risky=yes
```

## Decisoes tecnicas

### 1. PHP puro

O desafio pedia PHP sem framework. A escolha foi manter a aplicacao enxuta, explicita e facil de avaliar, sem esconder a logica atras de abstrações de framework.

### 2. Arquitetura em camadas

A separacao entre `Controller`, `Service` e `Repository` ajuda a isolar responsabilidades:

- controller recebe e devolve HTTP
- service aplica regras de negocio
- repository consulta o banco

Isso deixa o codigo mais legivel e reduz acoplamento.

### 3. Recorde pessoal por usuario

O ranking nao considera todos os registros brutos. Primeiro o sistema identifica o melhor recorde de cada usuario para o movimento consultado e, so depois, monta a classificacao final.

### 4. Empates

Usuarios com o mesmo valor compartilham a mesma posicao. O calculo do ranking segue o modelo competitivo:

```text
190, 180, 180, 170 -> 1, 2, 2, 4
```

### 5. Contrato JSON

O payload foi estruturado para representar entidades de forma clara:

- `movement`
- `ranking`
- `user`
- `personal_record`
- `meta`

Isso deixa a API mais semantica e mais facil de evoluir.

## Informacoes adicionais relevantes

### Implementacao alternativa considerada

Uma alternativa seria calcular toda a posicao diretamente no SQL com `RANK()`. O projeto manteve a selecao do recorde pessoal no banco e o calculo final das posicoes no service, o que reduz sensibilidade a detalhes de sintaxe SQL e deixa a regra de empate mais explicita no codigo da aplicacao.

### Sobre o frontend de demonstracao

O desafio pedia apenas o endpoint REST em PHP. Ainda assim, eu decidi entregar um frontend de demonstracao como plus por tres motivos:

1. Facilitar a avaliacao visual do funcionamento da API.
2. Tornar as regras de negocio mais evidentes, especialmente ranking, empate e resposta JSON.
3. Demonstrar cuidado com apresentacao tecnica, comunicacao de solucao e experiencia de uso.

O frontend nao substitui o backend nem mascara a entrega principal. Ele existe como uma camada de demonstracao para deixar mais claro, rapido e objetivo como o endpoint responde e como as regras do desafio foram implementadas.

## Autor

Matheus Coutinho
