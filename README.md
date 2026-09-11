# Relatório de Clientes

Aplicação de relatório em PHP, sem framework. O backend expõe um endpoint JSON
e o frontend consome esse endpoint via AJAX para montar a tabela.

O desafio pedia algo enxuto, então nenhuma dependência foi instalada: o
`composer.json` existe só para gerar o autoload PSR-4.

## Requisitos

- PHP 8.2 ou superior
- Composer

## Como rodar

```bash
composer install
```

```bash
php -S localhost:8000 -t public public/index.php
```

Abra `http://localhost:8000`.

Em Laravel Herd, Valet ou qualquer vhost, basta apontar o *document root* para
`public/` — o `.test` já funciona sem configuração extra.

## Estrutura

```
bootstrap/app.php      monta container, rotas e devolve o roteador
routes/web.php         declaração das rotas
public/index.php       front controller: recebe, despacha, responde
public/assets/         css e js servidos estaticamente
core/                  o mínimo de framework: Router, Container, Response
src/Controllers/       recebe a requisição já roteada
src/Repositories/      leitura e validação da fonte de dados
src/Views/             html do relatório
data/clientes.php      fonte de dados (array PHP, sem banco)
```

## Arquitetura

A organização é deliberadamente parecida com a do Laravel, que é o framework
que uso no dia a dia. A ideia foi mostrar que os conceitos são entendidos, e
não apenas usados quando o framework já os entrega prontos.

O caminho de uma requisição:

```
public/index.php  →  bootstrap/app.php  →  routes/web.php
        ↓
   Router::dispatch()
        ↓
   Container resolve o controller e suas dependências
        ↓
   CustomerController::index()  →  CustomerRepository::all()
        ↓
   Response::json()  →  send()
```

**`core/Router.php`** — guarda `método + caminho → handler`. Aceita closure ou
`[Classe::class, 'metodo']`. Caminho registrado em outro método responde `405`
com o header `Allow`; caminho desconhecido responde `404`.

**`core/Container.php`** — resolve dependências por reflexão. Ao instanciar
`CustomerController`, lê a assinatura do construtor, vê que ele precisa de um
`CustomerRepository` e o monta sozinho. Dependência que não é classe (uma
`string`, por exemplo) não tem como ser adivinhada e precisa de registro
explícito — é o que `bootstrap/app.php` faz com o caminho do arquivo de dados.

**`core/Response.php`** — todo retorno HTTP sai por aqui, então status,
`Content-Type` e cabeçalhos de segurança ficam definidos em um lugar só.

**`bootstrap/app.php`** — a *composition root*: o único ponto que sabe quais
implementações concretas existem e onde os dados moram. Fica fora de `public/`,
portanto não é acessível pela web.

## API

### `GET /api/customers`

```json
[
  {
    "id": 1,
    "nome": "Ana Pereira",
    "email": "ana.pereira@email.com",
    "cidade": "São Paulo",
    "telefone": "11987654321"
  }
]
```

Cabeçalhos: `Content-Type: application/json; charset=utf-8`,
`Cache-Control: no-store`, `X-Content-Type-Options: nosniff`.

### Erros

Abaixo de `/api`, erro sai em JSON. Fora dele, em texto — uma navegação de
browser não deveria receber JSON cru na tela.

```json
{ "erro": "Rota não encontrada." }
```

| Situação | Status |
| --- | --- |
| Rota inexistente | `404` |
| Método não registrado para a rota | `405` + header `Allow` |
| Fonte de dados ilegível, ausente ou corrompida | `500` |

## Decisões técnicas

**Validação fica na escrita, não na leitura.** A fonte é um array PHP versionado
junto com o projeto, então o repositório confia no formato e só trata falhas do
arquivo como um todo: ausente, ilegível ou com sintaxe inválida.

**Erro de sintaxe no arquivo de dados é tratado.** Em PHP 8 o `require` de um
arquivo com sintaxe inválida lança `ParseError`, que o repositório captura e
converte em falha de fonte de dados — resposta `500` em vez de página em
branco.

**Autoload PSR-4 via Composer, sem dependências.** O bloco `require` do
`composer.json` tem apenas a versão do PHP.

## Problemas encontrados no pacote original

O enunciado avisava que os arquivos continham erros propositais. Três deles
impediam a tela de funcionar:

1. **Contrato divergente entre front e back.** O JavaScript chamava
   `?action=list`, mas o PHP só tratava `action === 'report'`. Nenhum dos dois
   respondia ao outro — e, pior, a action desconhecida encerrava o script sem
   imprimir nada: resposta `200` com corpo vazio, que faz o `response.json()`
   estourar `Unexpected end of JSON input`.

2. **Elemento de destino inexistente.** O JavaScript procurava
   `getElementById('relatorios')` enquanto o HTML declarava `id="relatorio"`.
   `getElementById` devolve `null` silenciosamente, e a quebra só aparecia uma
   linha depois, em `Cannot set properties of null`.

3. **Colunas fora de ordem.** O cabeçalho da tabela dizia `Email | Nome |
   Cidade`, mas as células eram preenchidas com `nome`, `email`, `cidade`.
   Esse erro não gera exceção nenhuma: a tabela renderiza normalmente e mostra
   dado trocado, que é pior que uma tela quebrada.

Além disso, o endpoint original não verificava a existência do arquivo de
dados, não tratava exceção, não enviava status HTTP de erro, omitia o
`charset` no `Content-Type` e serializava sem `JSON_UNESCAPED_UNICODE` — os
acentos saíam escapados como `"São Paulo"`.

## O que ficou de fora

- Frontend: a tabela funciona, mas ainda não trata `response.ok`, não tem
  `.catch()` e monta o HTML com `innerHTML` a partir de dado não escapado.
  Estados de carregamento e de erro também não existem.
- Banco de dados: o enunciado dispensa, a fonte é um arquivo PHP.
- Rota com parâmetro (`/clientes/{id}`): o roteador casa caminho exato, o que
  basta para uma rota de relatório.
- Paginação: 47 registros cabem numa requisição.
