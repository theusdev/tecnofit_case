Escopo
Entregaremos 2 coisas:

Uma API REST em PHP puro, que é o coração do desafio.
Uma página estática de demonstração opcional, servida pelo próprio projeto, só para evidenciar visualmente o ranking e as regras.
Nada de arquitetura inflada, nada de camadas desnecessárias.

Arquitetura de pastas
Sugestão enxuta e profissional:
movement-ranking/
  public/
    index.php
    assets/
      app.js
      styles.css
  src/
    Http/
      Request.php
      Response.php
      Router.php
    Controller/
      RankingController.php
      HealthController.php
    Domain/
      RankingEntry.php
      MovementRanking.php
    Service/
      RankingService.php
      RankingPositionCalculator.php
    Repository/
      MovementRepository.php
      PersonalRecordRepository.php
    Database/
      Connection.php
    Exception/
      ValidationException.php
      NotFoundException.php
  config/
    app.php
    database.php
  database/
    schema.sql
    seed.sql
  tests/
    Unit/
      Service/
        RankingPositionCalculatorTest.php
        RankingServiceTest.php
    Integration/
      Http/
        RankingEndpointTest.php
      Repository/
        PersonalRecordRepositoryTest.php
  docs/
    http-examples.http
  .env.example
  composer.json
  phpunit.xml
  README.md
  docker-compose.yml
  Dockerfile

Responsabilidade de cada parte

public/index.php: front controller único.
Http/: abstrações mínimas de request/response/router.
Controller/: traduz HTTP para aplicação.
Service/: regra de negócio e orquestração.
Repository/: acesso ao MySQL.
Domain/: objetos claros do ranking.
tests/: separar unitário de integração.
public/assets/: demo simples em JS puro.
Rotas
Eu faria só o necessário:

GET /health
Retorna que a API está saudável.

GET /api/rankings?movement_id=1
Busca ranking por id.

GET /api/rankings?movement_name=Deadlift
Busca ranking por nome.

Regra: aceitar movement_id ou movement_name, mas não os dois ao mesmo tempo.

Isso é melhor do que inventar muitas rotas. Fica simples para testar e usar.

Contrato de sucesso
Exemplo para Deadlift:

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
    "generated_at": "2026-04-25T00:00:00Z"
  }
}

Contrato de erro
Padronize tudo em JSON:
{
  "error": {
    "code": "movement_not_found",
    "message": "Movement not found."
  }
}

Casos:

400 Bad Request: parâmetro ausente, inválido, ou ambos enviados.
404 Not Found: movimento não encontrado.
500 Internal Server Error: erro inesperado.
Como implementar a regra
O ponto principal é este:

Descobrir o movimento pelo id ou name.
Buscar, para cada usuário, o maior value naquele movimento.
Trazer também a date correspondente ao recorde pessoal.
Ordenar por value DESC.
Calcular a posição considerando empate.
Para o ranking, a regra deve ser:

mesmo valor => mesma posição
próxima posição pula conforme o ranking real
Exemplo: 190, 180, 180, 170 vira posições 1, 2, 2, 4.

Estratégia de query
Sem overkill, eu faria uma query SQL clara e eficiente no repositório, evitando lógica pesada no controller. Em MySQL 8, dá para resolver de forma limpa com subquery ou window function. Para este desafio, o mais importante é legibilidade e correção.

Estratégia de testes
TDD de verdade, mas pragmático:

Unitários
Testar a regra de posição isoladamente.
Casos:
ordenação decrescente
empate compartilha posição
próxima posição após empate
lista vazia
Unitários de serviço
Mockando repositório, testar:
busca por id
busca por nome
movimento inexistente
transformação correta para payload de domínio
Integração de repositório
Com banco de teste e seed:
retorna recorde pessoal correto por usuário
retorna a data correta do recorde
respeita movimento informado
Integração do endpoint
Subindo a aplicação:
200 por movement_id
200 por movement_name
400 para parâmetros inválidos
404 para movimento inexistente
payload final correto
Isso já é suficiente para passar maturidade sem virar laboratório de testes.

Demo frontend
Eu faria simples e útil:

um campo para buscar movimento
uma tabela com posição, usuário, recorde e data
uma aba com o JSON retornado pela API
uma aba curta “Regras atendidas”
Sem dashboard complexo, sem log viewer. A demo precisa reforçar a API, não roubar a cena.

Padrões de qualidade
O mínimo que vale muito:

PHP 8.3
Composer
PHPUnit
PSR-4
declare(strict_types=1);
.env.example
Docker Compose
phpstan nível moderado
php-cs-fixer ou pint
Isso já passa bastante seriedade.

Roadmap de implementação
Ordem que um sênior provavelmente seguiria:

Inicializar projeto com Composer, PSR-4, PHPUnit e Docker.
Criar schema.sql, seed.sql e validar banco local.
Definir contrato JSON e regras de erro.
Escrever testes unitários da regra de ranking.
Implementar RankingPositionCalculator.
Escrever testes do serviço de ranking.
Implementar repositórios e conexão com banco.
Escrever testes de integração do repositório.
Implementar controllers, request/response e roteamento.
Escrever testes de integração do endpoint.
Criar frontend demo simples em JS puro.
Escrever README forte com setup, rotas, exemplos e decisões técnicas.
Rodar lint, análise estática, testes e revisão final de nomes e estrutura.
