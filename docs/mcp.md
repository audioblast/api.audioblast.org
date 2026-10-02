# The MCP server

AI applications that support the Model Context Protocol (MCP), such as Claude,
can query audioBLAST! through its MCP server at **https://api.audioblast.org/mcp**.
The server only reads. Its tools reach the API's data modules (recordings, taxa,
references, specimens, traits, links and the others) and give what the API's own
endpoints give, because they are built from the same functions. Analysis,
standalone and source modules are not reached.

## Connecting to it

| Application | How |
|---|---|
| Claude Code | `claude mcp add --transport http audioblast https://api.audioblast.org/mcp`. Add `--scope user` to use it in every project. |
| Claude (claude.ai and the Claude apps) | Add a custom connector with the server's address. Claude connects to it from Anthropic's servers. |
| Other applications | Any MCP client that supports the Streamable HTTP transport. |

No login or key is needed. The vocabulary the links use has an MCP server of its
own, at https://vocab.audioblast.org/api/mcp.

## Tools

| Tool | Arguments | Gives |
|---|---|---|
| `list_modules` | none | Each data module's name, title, description, and the URI template its records are identified by (null where they have none). |
| `describe_module` | `module` | The module's description and source notes, its filters (each with its description, type, how it matches, whether it takes several values, its allowed values, and whether `suggest_values` gives its values), what each way of matching means, every field a record has, and its record URI template. |
| `query_module` | `module`; `filters` (optional) from filter name to a value, or a list of values; `page` (from 1); `page_size` (1 to 100, default 20); `count` (default false) | A page of records, each with its `record_uri` first where the module's records have URIs. `more` says whether there is another page. `total` is the number of matching records when `count` is true, and null otherwise. |
| `suggest_values` | `module`; `field`; `text` (optional); `match` (`starts`, the default, or `contains`); `filters` (optional); `limit` (1 to 100, default 20) | The distinct values of the field, as `/data/{module}/autocomplete/{field}/` gives them, and `more`. This is how a model finds the exact value an exact filter needs, such as a taxon's name. |
| `get_record` | `uri`, or `module`, `source` and `id` | The record, its canonical URI, and its linked data as JSON-LD, as the record's URI gives it: the links to and from it, and for a taxon its whole classification. Linked data larger than 200,000 bytes is left out, with a note on how to page through the links instead. |

Filters are checked as the API checks a request's parameters (`checkParams()` in
`core/input.php`), so a filter the module doesn't have is refused with a list of
those it does. `output` and `format` are not filters here: tools always give
records as JSON, with the module's own field names.

How a filter matches, as `describe_module` gives it:

| Match | Means |
|---|---|
| `exact` | The field is the value, letter case aside. A filter that takes several values matches any of them, given as a list or joined with commas. |
| `contains` | The field contains the value. |
| `words` | Full-text search: records with a word starting with any one of the words given, so `Gryllus campestris` also finds every other `Gryllus`. |
| `range` | `min:max` (both ends included), `>=x`, `<=x`, `>x`, `<x`, or a number alone. |

Each result is given as structured data matching the tool's output schema, and
as the same JSON in text. When a tool can't answer (an unknown module or
filter, a record that isn't held, a page size out of range, a failed query) the
result is an error that says what to change. SQL is never given back.

The instructions the server gives applications say what audioBLAST! holds, how
the tools fit together, and to give a record's URI when using it, and for a
recording its author and license as well.

## How requests work

Clients post one JSON-RPC 2.0 message per request to `/mcp`, and the server
answers with JSON (Streamable HTTP, without streaming). It keeps no sessions.

| Version | How clients use it |
|---|---|
| 2026-07-28 | Each request gives its protocol version and the client's capabilities in `_meta`, and repeats the version, method and tool name in the `MCP-Protocol-Version`, `Mcp-Method` and `Mcp-Name` headers, which must match it. `server/discover` gives the versions, capabilities and instructions. |
| 2025-11-25, 2025-06-18 and 2025-03-26 | Clients start with `initialize`, and may `ping`. |

```
curl -X POST https://api.audioblast.org/mcp -H "Content-Type: application/json" \
  -H "MCP-Protocol-Version: 2025-06-18" \
  --data '{"jsonrpc": "2.0", "id": 1, "method": "tools/call", "params": {"name": "query_module",
    "arguments": {"module": "taxa", "filters": {"taxon": "Gryllus campestris"}}}}'
```

| Request | Response |
|---|---|
| One the server answers, including a tool call that is an error, or that names a tool the server doesn't have (-32602) | 200 |
| A notification, such as `notifications/initialized` | 202, with no body |
| OPTIONS | 204 |
| A body that isn't JSON (-32700) or a single JSON-RPC 2.0 message (-32600), missing `_meta` fields (-32602), headers that don't match the request (-32020), or an unsupported protocol version (-32022, listing those supported) | 400 |
| A method the server doesn't have (-32601) | 404 for 2026-07-28; 200 for earlier versions, whose clients may take an HTTP error as a failed connection |
| GET, DELETE or any other method | 405 |
| A body of more than 64 KB | 413 |

## Behind Cloudflare

Requests to the MCP server are posts from programs, not browsers, so
Cloudflare's bot protection must let POST and OPTIONS requests to `/mcp`
through, and must not cache them. The cross-origin headers
(`Access-Control-Allow-Origin: *` and the allowed methods and headers) are added
to every response by the server in front of the API, so `core/mcp.php` doesn't
send them: sending them twice would make browsers refuse the response.

## The code

- `core/mcp.php` handles the protocol. It is Ontomasticon's (`core/mcp.php`, as
  of ontomasticon 631db33), which vocab.audioblast.org runs, with its functions
  kept in the same names and order so that a fix can be carried from one to the
  other.
- `core/mcp-tools.php` holds the tools.
- `php tests/mcp.php` tests both, against a fixture in place of the database.
