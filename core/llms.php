<?php

/*
Pointing AI crawlers at an llms.txt.

llms.txt (https://llmstxt.org) is a plain-text/markdown file, at a site's root,
that tells language models what a site offers and where to look. acousti.cloud
publishes one for the wider project; this serves a copy of it at
api.audioblast.org/llms.txt (serveLlmsTxt), where a crawler looking for the file
at the root will find it, and adds a Link header pointing at that copy for
crawlers that read one (llmsLinkHeader).
*/

//The user-agent tokens of the AI crawlers we answer. Matched case-insensitively
//as substrings, so each has to be specific enough not to catch an ordinary
//browser or search crawler -- `bot` on its own would match almost everything.
//New crawlers appear and these tokens date, so the list is kept small, explicit
//and in this one place; robots.txt-only product tokens (Google-Extended,
//Applebot-Extended) are left out, as they never appear in a user-agent.
function aiIndexerTokens() {
  return(array(
    "GPTBot",              //OpenAI
    "OAI-SearchBot",       //OpenAI
    "ChatGPT-User",        //OpenAI, user-triggered fetch
    "ClaudeBot",           //Anthropic
    "Claude-SearchBot",    //Anthropic
    "Claude-User",         //Anthropic, user-triggered fetch
    "anthropic-ai",        //Anthropic, older token
    "CCBot",               //Common Crawl
    "PerplexityBot",       //Perplexity
    "Perplexity-User",     //Perplexity, user-triggered fetch
    "Amazonbot",           //Amazon
    "Bytespider",          //ByteDance
    "Meta-ExternalAgent",  //Meta
    "meta-externalfetcher",//Meta, user-triggered fetch
    "cohere-ai",           //Cohere
    "MistralAI-User",      //Mistral
    "DuckAssistBot",       //DuckDuckGo
    "YouBot",              //You.com
    "Diffbot"              //Diffbot
  ));
}

//Whether a user-agent string is one of the AI crawlers in aiIndexerTokens().
function isAIIndexer($ua) {
  if ($ua === NULL) {return(FALSE);}
  $ua = strtolower($ua);
  foreach (aiIndexerTokens() as $token) {
    if (strpos($ua, strtolower($token)) !== FALSE) {return(TRUE);}
  }
  return(FALSE);
}

//Serve the local copy of acousti.cloud's llms.txt and stop. It needs no
//database, so core.php calls it before connecting. The file is read by an
//absolute path, so the current working directory does not matter.
function serveLlmsTxt() {
  $path = __DIR__."/../llms.txt";
  if (!is_readable($path)) {
    http_response_code(404);
    header("Content-Type: text/plain; charset=utf-8");
    print("llms.txt is not available");
    exit;
  }
  header("Content-Type: text/plain; charset=utf-8");
  readfile($path);
  exit;
}

//For an AI crawler, add a Link header pointing at this host's llms.txt. The
//`false` lets it sit alongside any other Link header (such as the `rel="next"`
//of a paginated RDF reply) rather than replacing or being replaced by it.
function llmsLinkHeader() {
  if (isAIIndexer($_SERVER["HTTP_USER_AGENT"] ?? NULL)) {
    header('Link: <https://api.audioblast.org/llms.txt>; rel="llms.txt"', false);
  }
}
