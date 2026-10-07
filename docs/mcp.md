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
| `suggest_values` | `module`; `field`; `text` (optional); `match` (`starts`, the default, or `contains`); `filters` (optional); `limit` (1 to 100, default 20) | The distinct values of the field, as `/data/{module}/autocomplete/{field}/` gives them, in no particular order, and `more`. This is how a model finds the exact value an exact filter needs, such as a taxon's name. On a field with a full-text index (the names of taxa and recordings, say), `contains` matches as `words` does. |
| `get_record` | `uri`, or `module`, `source` and `id` | The record, its canonical URI, and its linked data as JSON-LD, as the record's URI gives it: the links to and from it, for a recording the regions of interest annotations mark on it (`ac:hasROI`), and for a taxon its whole classification. Linked data larger than 200,000 bytes is left out, with a note on how to page through the links instead, and for a recording with regions, how to find them with `query_module` on `annomate` by its `recording_source` and `source_id`. A record's details are never included, as details are not given as linked data. |

Filters are checked as the API checks a request's parameters (`checkParams()` in
`core/input.php`), so a filter the module doesn't have is refused with a list of
those it does. Only a filter that takes several values splits its value at
commas: any other takes a comma as part of the value, so `country` `GB,FR`
finds nothing. `output` and `format` are not filters here: tools always give
records as JSON, with the module's own field names.

How a filter matches, as `describe_module` gives it:

| Match | Means |
|---|---|
| `exact` | The field is the value, letter case aside. A filter that takes several values matches any of them, given as a list or joined with commas. |
| `contains` | The field contains the value. |
| `words` | Full-text search: records with any one of the words given, the last of them as the start of a word, so `Gryllus campestris` also finds every other `Gryllus`, and every other `campestris`, such as `Anthus campestris`. |
| `range` | `min:max` (both ends included), `>=x`, `<=x`, `>x`, `<x`, or a number alone. |

Each result is given as structured data matching the tool's output schema, and
as the same JSON in text. When a tool can't answer (an unknown module or
filter, a record that isn't held, a page size out of range, a failed query) the
result is an error that says what to change. SQL is never given back.

The instructions the server gives applications say what audioBLAST! holds, how
the tools fit together, and to give a record's URI when using it, and for a
recording its author and licence as well, or that it gives none. Then they say
how to read the records rightly, and last how to find corpora and their
regions, as below.

## Reading the data

Some things about the data would give a model a wrong answer unless it is told.
What holds whichever module is queried is in the instructions
(`mcpDataInstructions()` in `core/mcp-tools.php`):

- Names are written as each source writes them, and exact filters match only
  that form. bio.acousti.ca writes a subgenus without brackets, so `traits`
  with `taxon` `Gryllus campestris` finds nothing while `Gryllus Gryllus
  campestris` finds 47 (7 October 2026). Models are told to look for other
  forms with `suggest_values` before saying nothing is held.
- Each source has its own taxa records. They are matched to the Catalogue of
  Life's (source `CoL`) by `skos:exactMatch` links, which gather one taxon's
  records from every source.
- A recording's "is about" link to a taxon with the qualifier
  `https://vocab.audioblast.org/cv/recordingContent#NonFocalTaxa` is to a
  species only heard in the background.
- An empty licence is unknown, not free; most predicates are not defined at
  vocab.audioblast.org, and some of its qualifiers aren't yet either.

What holds for one module is in that module's `source_notes`, which
`describe_module` gives and the API's own documentation shows: soundscapes and
the Animal Sound Archive's shared files in `recordings`, repeated rows in
`recordingstaxa`, units and inferred values in `traits`, and the kinds of
annotation in `annomate`.

## Corpora

A corpus, such as a set of regions marked in recordings to train classifiers,
is a `references` record. There is no tool for corpora: they are found with the
tools above, as the server's instructions tell clients.

| To find | Use |
|---|---|
| Every corpus | `query_module` on `links`, with `predicate` `http://purl.org/dc/terms/type` and `object_id` `https://vocab.audioblast.org/Corpus`. Each link's `subject_source` and `subject_id` are a corpus's source and id. |
| A corpus's regions of interest, a page at a time | `query_module` on `links`, with `predicate` `http://purl.org/dc/terms/isPartOf`, `object_type` `references`, and the corpus's source and id as `object_source` and `object_id`. Each link's `subject_id` is a region's `annotation_id`, and its `qualifier` is the split the region is in, such as `Training` or `Validation`, which the `qualifier` filter chooses. |
| The regions themselves | `query_module` on `annomate`, with up to 100 of those ids as `annotation_id`. A region's recording is its `recording_source` and `source_id`, and its frequency bounds, in Hz, are its `freq_low` and `freq_high`. |
| A region's other values | `query_module` on `details`, with `type` `annomate`, `record_source` the region's source and `id` its `annotation_id`, or a list of them, such as `svl_label`. |
| The version a corpus was made from | `query_module` on `links`, with the corpus as `subject_type`, `subject_source` and `subject_id`. A later version links to the one it came from by `http://purl.org/dc/terms/source`, and has its own regions. |
| Every annotation of a recording, whichever source gave it | `query_module` on `annomate`, with `recording_source` and `source_id`. |

`https://vocab.audioblast.org/Corpus` is a placeholder until the term is
defined at vocab.audioblast.org. The code names it once, as `MCP_CORPUS_TYPE`
in `core/mcp-tools.php`. `tests/mcp.php` checks that every filter the
instructions name is still one its module has, matching as the steps need.

What clients can't yet do:

- `get_record` gives a corpus without its linked data, because the links to
  thousands of regions are far more than 200,000 bytes. This also leaves out
  the corpus's own few links: its type, and the version it came from.
- `annomate` has no filter that picks out one corpus. Its `source` filter gives
  every region the source holds, of whichever corpus or version, so listing a
  corpus's regions takes two calls for every 100: a page of links, then
  `annomate` by `annotation_id`.
- A region's split is only in the links' JSON. Linked data gives a link's
  qualifier only when it is an IRI, so `Training` and `Validation` are left out
  of a region's JSON-LD.

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
