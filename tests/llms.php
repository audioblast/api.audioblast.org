<?php
// No settings or real database are loaded. Run from the repository root.
require 'core/llms.php';
set_error_handler(function($severity, $message, $file, $line) {
  throw new ErrorException($message, 0, $severity, $file, $line);
});
function check($condition, $message) {
  if (!$condition) {throw new Exception($message);}
}

// The AI crawlers we point at the llms.txt, in realistic user-agent strings.
$indexers = array(
  "Mozilla/5.0 AppleWebKit/537.36 (KHTML, like Gecko); compatible; GPTBot/1.2; +https://openai.com/gptbot",
  "Mozilla/5.0 (compatible; OAI-SearchBot/1.0; +https://openai.com/searchbot)",
  "Mozilla/5.0 (compatible; ClaudeBot/1.0; +claudebot@anthropic.com)",
  "Mozilla/5.0 (compatible; CCBot/2.0; https://commoncrawl.org/faq/)",
  "Mozilla/5.0 (compatible; PerplexityBot/1.0; +https://perplexity.ai/perplexitybot)",
  "Mozilla/5.0 (compatible; Amazonbot/0.1; +https://developer.amazon.com/support/amazonbot)",
  "Mozilla/5.0 (compatible; Bytespider; https://zhanzhang.toutiao.com/)",
  "meta-externalagent/1.1 (+https://developers.facebook.com/docs/sharing/webmasters/crawler)",
  "anthropic-ai"
);
foreach ($indexers as $ua) {
  check(isAIIndexer($ua), "An AI crawler is recognised: ".$ua);
}

// Ordinary browsers and ordinary (non-AI) search crawlers are left alone, as is
// a blank or missing user-agent.
$others = array(
  "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36",
  "Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)",
  "Mozilla/5.0 (compatible; bingbot/2.0; +http://www.bing.com/bingbot.htm)",
  "curl/8.4.0",
  ""
);
foreach ($others as $ua) {
  check(!isAIIndexer($ua), "A non-AI agent is not matched: ".$ua);
}
check(!isAIIndexer(NULL), "A missing user-agent is not matched");

// The match ignores case, and does not fire on a lookalike token.
check(isAIIndexer("mozilla/5.0 (compatible; gptbot/1.2)"), "The match ignores case");
check(!isAIIndexer("GPTBrowser/1.0"), "A lookalike token is not matched");

print("llms: all checks passed\n");
