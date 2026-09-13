# Relatório de Clientes

Relatório em PHP sem framework: um endpoint JSON consumido via AJAX por uma
tela em HTML, CSS e JavaScript.

**[Assistir ao vídeo de apresentação](https://www.youtube.com/watch?v=7joednuWufE)** —
o relatório funcionando, os problemas encontrados e as decisões de projeto.

## Como rodar

Requer PHP 8.2+ e Composer.

```bash
composer install
php -S localhost:8000 -t public public/index.php
```

Abra `http://localhost:8000`. Em Herd ou Valet, aponte o *document root* para
`public/`.

## Estrutura

```
public/index.php     front controller
bootstrap/app.php    monta o container e as rotas
routes/web.php       declaração das rotas
core/                Router, Container, Request, Response
src/                 controller, repositório e view
data/clientes.php    fonte de dados
```

A organização segue a do Laravel: o `Container` resolve dependências por
reflexão, o `Router` entrega um `Request` para a action e toda resposta sai por
`Response`. Nenhuma dependência instalada — o Composer serve só para o
autoload PSR-4.

## API

`GET /api/customers?busca=<termo>` — lista os clientes; com `busca`, filtra
por nome, e-mail ou cidade.

Erros sob `/api` saem em JSON: `404` para rota inexistente, `405` para método
não permitido (com header `Allow`) e `500` quando a fonte de dados está
indisponível.

## Problemas encontrados no pacote original

1. **Contrato divergente** — o JS pedia `action=list` e o PHP só tratava
   `report`. A action desconhecida devolvia `200` com corpo vazio, e o
   `response.json()` quebrava.
2. **Elemento inexistente** — o JS buscava `#relatorios` e o HTML tinha
   `#relatorio`; o `getElementById` devolvia `null`.
3. **Colunas trocadas** — cabeçalho `Email | Nome` com células `nome | email`.
   Não gerava erro nenhum: mostrava dado errado.

Além disso: nenhum tratamento de erro no PHP, `Content-Type` sem charset,
acentos escapados (`S\u00e3o Paulo`) e, no front, sem `response.ok` nem
`.catch()` — qualquer falha deixava a tela em branco.

## Decisões

- **Filtro no servidor**, com `array_filter`: o JavaScript só monta a URL e
  desenha o resultado.
- **Validação na escrita, não na leitura**: a fonte é versionada no projeto,
  então o repositório só trata falha do arquivo inteiro (ausente, ilegível ou
  com `ParseError`) e responde `500` em JSON.
- **Telefone sem máscara na API**: a formatação acontece só na exibição.
- **Tabela com `innerHTML`**: seguro aqui porque nenhum dado digitado pelo
  usuário é renderizado. Se a fonte passar a ser formulário ou banco, trocar
  por `textContent`.
